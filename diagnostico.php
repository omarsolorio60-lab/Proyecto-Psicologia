<?php
/**
 * Página de diagnóstico: abre http://localhost/<carpeta-del-proyecto>/diagnostico.php
 * y te dice qué falta para que todo funcione. Solo responde desde tu equipo o red local.
 * BÓRRALA antes de publicar el sistema.
 */
declare(strict_types=1);
require_once __DIR__ . '/config/cuenta_helpers.php';

header('Content-Type: text/html; charset=utf-8');
if (!es_red_local()) {
    http_response_code(403);
    exit('Diagnóstico disponible solo desde la red local.');
}

$filas = [];
$ok = static function (string $t, string $d = '') use (&$filas) { $filas[] = ['ok', $t, $d]; };
$mal = static function (string $t, string $d) use (&$filas) { $filas[] = ['mal', $t, $d]; };
$aviso = static function (string $t, string $d) use (&$filas) { $filas[] = ['aviso', $t, $d]; };

// 1. PHP y extensiones
version_compare(PHP_VERSION, '7.4.0', '>=') ? $ok('PHP ' . PHP_VERSION) : $mal('PHP ' . PHP_VERSION, 'Se requiere PHP 7.4 o superior. Actualiza XAMPP.');
foreach (['pdo_mysql' => 'Base de datos', 'curl' => 'Validar a Google', 'fileinfo' => 'Validar el archivo del horario', 'mbstring' => 'Nombres con acentos', 'openssl' => 'Conexión segura', 'json' => 'JSON'] as $ext => $para) {
    extension_loaded($ext) ? $ok("Extensión $ext", $para)
        : $mal("Extensión $ext", "Falta ($para). En XAMPP: Config del botón Apache → PHP (php.ini) → quita el ';' de extension=$ext y reinicia Apache.");
}

// 2. Base de datos
$pdo = null;
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME), DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $ok('Conexión a MySQL', 'Base de datos "' . DB_NAME . '"');
} catch (Throwable $e) {
    $mal('Conexión a MySQL', 'No se pudo conectar: ' . $e->getMessage() . ' → Inicia MySQL en el panel de XAMPP e importa database/sigep_schema_sin_datos.sql en phpMyAdmin. Revisa DB_USER/DB_PASS en config/config.php.');
}
if ($pdo) {
    foreach (['students', 'appointments', 'psychologists', 'appointment_slots', 'programs', 'users', 'applicants'] as $tabla) {
        try {
            $n = (int) $pdo->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();
            $ok("Tabla $tabla", "$n registro(s)");
        } catch (Throwable $e) {
            $mal("Tabla $tabla", in_array($tabla, ['programs', 'users'], true)
                ? 'No existe. Si ya tenías sigep_db, importa database/migracion_hu14.sql; si es nueva, database/sigep_schema_sin_datos.sql.'
                : 'No existe. Importa database/sigep_schema_sin_datos.sql en phpMyAdmin.');
        }
    }
    try {
        $pdo->query('SELECT user_id, sex, program_id, group_code, schedule_file, link_needs_review FROM students LIMIT 0');
        $ok('Columnas de la HU-14 en students', 'La base ya está migrada');
    } catch (Throwable $e) {
        $mal('Columnas de la HU-14 en students', 'Faltan. Importa database/migracion_hu14.sql en phpMyAdmin (una sola vez).');
    }
    try {
        $n = (int) $pdo->query('SELECT COUNT(*) FROM psychologists WHERE active = 1')->fetchColumn();
        $s = (int) $pdo->query('SELECT COUNT(*) FROM appointment_slots WHERE slot_date > NOW()')->fetchColumn();
        ($n > 0 && $s > 0) ? $ok('Citas', "$n psicóloga(s) activa(s) · $s horario(s) futuros")
            : $aviso('Citas', 'No hay psicólogas activas u horarios futuros: nadie podrá pedir cita. Para pruebas importa database/datos_prueba.sql.');
    } catch (Throwable $e) {
    }
}

// 3. Carpeta del horario
$dir = HORARIOS_DIR;
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}
is_dir($dir) && is_writable($dir) ? $ok('Carpeta storage/horarios', 'Se puede escribir')
    : $mal('Carpeta storage/horarios', 'No existe o no se puede escribir. Créala y dale permisos de escritura.');

// 4. Límites de subida
$need = HORARIO_MAX_BYTES;
foreach (['upload_max_filesize', 'post_max_size'] as $clave) {
    $b = ini_a_bytes((string) ini_get($clave));
    ($b <= 0 || $b >= $need + 1024 * 1024) ? $ok("php.ini · $clave", (string) ini_get($clave))
        : $mal("php.ini · $clave", ini_get($clave) . ' es muy bajo. Súbelo a 8M en php.ini y reinicia Apache.');
}

// 5. Google
if (GOOGLE_CLIENT_ID === '') {
    $aviso('GOOGLE_CLIENT_ID vacío', DEV_MODE ? 'Normal para probar: se usará el botón "Entrar sin Google (pruebas)".' : 'DEV_MODE está en false y no hay Client ID: nadie podrá entrar.');
} elseif (!preg_match('/^[0-9]+-[a-z0-9]+\.apps\.googleusercontent\.com$/i', GOOGLE_CLIENT_ID)) {
    $mal('GOOGLE_CLIENT_ID', 'No tiene el formato esperado (123456789-abc.apps.googleusercontent.com). Revisa que no sobren espacios o comillas.');
} else {
    $ok('GOOGLE_CLIENT_ID configurado');
}
DEV_MODE ? $aviso('DEV_MODE = true', 'Correcto para desarrollo. Ponlo en false antes de publicar.') : $ok('DEV_MODE = false');

// 6. ¿Este servidor puede hablar con Google? (un token falso debe responder 400, no un error SSL)
if (function_exists('curl_init')) {
    $ch = curl_init(GOOGLE_TOKENINFO_URL . '?id_token=prueba');
    $o = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 5];
    if (CA_BUNDLE !== '') { $o[CURLOPT_CAINFO] = CA_BUNDLE; }
    curl_setopt_array($ch, $o);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($code > 0) {
        $ok('Conexión con Google', 'El servidor puede validar tokens de Google');
    } elseif (in_array($errno, [35, 51, 58, 59, 60, 77], true)) {
        $mal('Conexión con Google', 'Error de certificado SSL. Descarga https://curl.se/ca/cacert.pem y pon su ruta en CA_BUNDLE (config/config.php).');
    } else {
        $aviso('Conexión con Google', 'No hay internet o está bloqueado. Sin esto solo funcionará el modo pruebas.');
    }
}
$todoBien = !in_array('mal', array_column($filas, 0), true);
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diagnóstico · SIGEP</title>
<style>
body{font:16px/1.45 system-ui,Segoe UI,Arial,sans-serif;background:#f2f7f3;color:#14281c;margin:0;padding:20px}
main{max-width:760px;margin:0 auto}h1{color:#004d2b;margin-top:0}
.f{background:#fff;border:1px solid #c9d6cd;border-left-width:6px;border-radius:10px;padding:10px 14px;margin:8px 0}
.ok{border-left-color:#0a6e3a}.mal{border-left-color:#b3261e;background:#fdf1f0}.aviso{border-left-color:#e0971a;background:#fff8e8}
.t{font-weight:700}.d{color:#4a5a50;font-size:14px;margin-top:2px}.res{padding:12px 14px;border-radius:10px;font-weight:700;margin:14px 0}
.res.ok{background:#e3f1e8;color:#004d2b}.res.mal{background:#fde8e6;color:#b3261e}
</style></head><body><main>
<h1>Diagnóstico del sistema</h1>
<div class="res <?= $todoBien ? 'ok' : 'mal' ?>"><?= $todoBien ? '✔ Todo listo. Abre la carpeta del proyecto en el navegador para probar.' : '✖ Hay cosas por corregir (marcadas en rojo).' ?></div>
<?php foreach ($filas as [$tipo, $t, $d]): ?>
  <div class="f <?= $tipo ?>"><div class="t"><?= $tipo === 'ok' ? '✔' : ($tipo === 'mal' ? '✖' : '⚠') ?> <?= htmlspecialchars($t) ?></div>
  <?php if ($d !== ''): ?><div class="d"><?= htmlspecialchars($d) ?></div><?php endif; ?></div>
<?php endforeach; ?>
<p class="d">Borra este archivo (diagnostico.php) antes de publicar el sistema.</p>
</main></body></html>
