<?php
/**
 * Pruebas automáticas de SIGEP (HU-14 registro + integración con citas).   Ejecutar:   php tests/probar.php
 * Usa una copia temporal del proyecto y una base de datos APARTE (sigep_test):
 * no toca tus datos reales. Requiere MySQL encendido (como en XAMPP).
 */
declare(strict_types=1);
$raiz = dirname(__DIR__);
require $raiz . '/config/config.php';

$testDb = 'sigep_test';
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sigep_test_' . uniqid();
$procs = [];
$pasaron = 0; $fallaron = 0;

function copiar(string $de, string $a): void {
    mkdir($a, 0777, true);
    foreach (scandir($de) as $x) {
        if (in_array($x, ['.', '..', 'tests', '.git', 'node_modules'], true)) continue;
        $o = "$de/$x"; $d = "$a/$x";
        if ($x === 'horarios') { mkdir($d, 0777, true); continue; }
        is_dir($o) ? copiar($o, $d) : copy($o, $d);
    }
}
function limpiar(string $d): void {
    if (!is_dir($d)) return;
    foreach (scandir($d) as $x) { if ($x === '.' || $x === '..') continue; is_dir("$d/$x") ? limpiar("$d/$x") : @unlink("$d/$x"); }
    @rmdir($d);
}
function arrancar(array $cmd, array $env, array &$procs, int $puerto): void {
    $nul = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
    $p = proc_open($cmd, [0 => ['file', $nul, 'r'], 1 => ['file', $nul, 'w'], 2 => ['file', $nul, 'w']], $pipes, null, $env);
    $procs[] = $p;
    for ($i = 0; $i < 50; $i++) { if (@fsockopen('127.0.0.1', $puerto)) return; usleep(100000); }
    throw new RuntimeException("No arrancó el servidor en el puerto $puerto");
}
function fin(array $procs, string $tmp, ?PDO $pdo, string $testDb): void {
    foreach ($procs as $p) { if (is_resource($p)) { $s = proc_get_status($p); if (PHP_OS_FAMILY !== 'Windows') { @exec('pkill -P ' . (int) $s['pid'] . ' 2>/dev/null'); } proc_terminate($p); } }
    if ($pdo) { try { $pdo->exec("DROP DATABASE IF EXISTS `$testDb`"); } catch (Throwable $e) {} }
    limpiar($tmp);
}
/** Ejecuta un archivo .sql sentencia por sentencia (separadas por ";" al final de línea). */
function importar(PDO $pdo, string $sql): void {
    foreach (preg_split('/;\s*\n/', $sql) as $st) {
        $st = trim(preg_replace('/^--.*$/m', '', $st));
        if ($st !== '') $pdo->exec($st);
    }
}

try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT), DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true]);
} catch (Throwable $e) { exit("✖ No se pudo conectar a MySQL ({$e->getMessage()}). ¿Está iniciado?\n"); }

$pdo->exec("DROP DATABASE IF EXISTS `$testDb`");
importar($pdo, str_replace('sigep_db', $testDb, file_get_contents($raiz . '/database/sigep_schema_sin_datos.sql')));
$pdo->exec("USE `$testDb`");

copiar($raiz, $tmp);
$cfg = file_get_contents("$tmp/config/config.php");
$cfg = preg_replace("/define\('DB_NAME',\s*'[^']*'\)/", "define('DB_NAME', '$testDb')", $cfg);
$cfg = preg_replace("/define\('GOOGLE_CLIENT_ID',\s*'[^']*'\)/", "define('GOOGLE_CLIENT_ID', 'test-client.apps.googleusercontent.com')", $cfg);
$cfg = preg_replace("/define\('DEV_MODE',\s*\w+\)/", "define('DEV_MODE', true)", $cfg);
file_put_contents("$tmp/config/config.php", $cfg);

$pApp = 8791; $pMock = 8792;
$env = array_merge(getenv(), ['SIGEP_TOKENINFO_URL' => "http://127.0.0.1:$pMock/tokeninfo"]);
try {
    arrancar([PHP_BINARY, '-S', "127.0.0.1:$pMock", $raiz . '/tests/mock_tokeninfo.php'], $env, $procs, $pMock);
    arrancar([PHP_BINARY, '-d', 'upload_max_filesize=40M', '-d', 'post_max_size=40M', '-S', "127.0.0.1:$pApp", '-t', $tmp], $env, $procs, $pApp);
} catch (Throwable $e) { fin($procs, $tmp, $pdo, $testDb); exit('✖ ' . $e->getMessage() . "\n"); }

$base = "http://127.0.0.1:$pApp/";
const CUENTA = 'backend/cuenta/';

function cliente(): array { return ['jar' => tempnam(sys_get_temp_dir(), 'jar')]; }
function http(array $c, string $metodo, string $ruta, $cuerpo = null, array $cab = []): array {
    global $base;
    $ch = curl_init($base . $ruta);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_COOKIEJAR => $c['jar'], CURLOPT_COOKIEFILE => $c['jar'], CURLOPT_HTTPHEADER => $cab, CURLOPT_TIMEOUT => 30]);
    if ($cuerpo !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $cuerpo);
    $r = (string) curl_exec($ch);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $st = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $head = substr($r, 0, $hs); $body = substr($r, $hs);
    return ['status' => $st, 'json' => json_decode($body, true), 'raw' => $body, 'head' => $head];
}
function tok(string $email, array $extra = []): string { return base64_encode(json_encode(['email' => $email] + $extra)); }
function login(array $c, string $email, array $extra = []): array {
    return http($c, 'POST', CUENTA . 'auth_google.php', json_encode(['credential' => tok($email, $extra)]), ['Content-Type: application/json']);
}
function valido(array $o = []): array {
    return $o + ['matricula' => '7000001', 'nombre' => 'Ana', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'López',
        'sexo' => 'M', 'telefono' => '686 123 4567', 'programa_id' => '1', 'semestre' => '3', 'grupo' => '331'];
}
function registrar(array $c, array $campos, ?string $contenido = null, string $nombre = 'h.png'): array {
    if ($contenido !== null) {
        $f = tempnam(sys_get_temp_dir(), 'up'); file_put_contents($f, $contenido);
        $campos['horario'] = new CURLFile($f, 'application/octet-stream', $nombre);
    }
    return http($c, 'POST', CUENTA . 'registro.php', $campos);
}
/** Abre la página de citas (obtiene el token CSRF) y envía una solicitud de cita. */
function pedir_cita(array $c, array $alumno, string $fecha, string $hora = '10:00'): array {
    $pagina = http($c, 'GET', 'frontend/citas/index.php');
    if (!preg_match('/id="csrf" value="([a-f0-9]+)"/', $pagina['raw'], $m)) throw new Exception('sin token CSRF en la página de citas');
    $cuerpo = ['csrf' => $m[1], 'alumno' => $alumno, 'fecha' => $fecha, 'hora' => $hora, 'modalidad' => 'Presencial', 'motivo' => 'Académico', 'detalle' => ''];
    return http($c, 'POST', 'backend/save_appointment.php', json_encode($cuerpo), ['Content-Type: application/json']);
}
function alumno_cita(array $o = []): array {
    return $o + ['matricula' => '7100001', 'nombre' => 'Luis', 'apellido_paterno' => 'Cita', 'apellido_materno' => '',
        'correo_institucional' => 'luis.cita@uabc.edu.mx', 'telefono' => '', 'carrera' => '', 'semestre' => ''];
}
function eq($a, $b, string $m = ''): void { if ($a !== $b) throw new Exception("$m esperado " . json_encode($b) . ', obtuvo ' . json_encode($a)); }
function cuenta(PDO $p, string $sql, array $a = []): int { $s = $p->prepare($sql); $s->execute($a); return (int) $s->fetchColumn(); }
function prueba(string $nombre, callable $fn): void {
    global $pasaron, $fallaron;
    try { $fn(); $pasaron++; echo "  ✔ $nombre\n"; } catch (Throwable $e) { $fallaron++; echo "  ✖ $nombre\n      → " . $e->getMessage() . "\n"; }
}
$PNG = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');   // PNG real de 1x1
$PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
$bd = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, $testDb), DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$carpetaUp = "$tmp/storage/horarios";

// Para las pruebas de citas: 2 psicólogas y un horario habilitado en un día hábil futuro.
$dia = new DateTimeImmutable('tomorrow');
while ((int) $dia->format('N') > 5) { $dia = $dia->modify('+1 day'); }
$FECHA = $dia->format('Y-m-d');
$bd->exec("INSERT INTO psychologists (full_name, institutional_email) VALUES ('A. López','p1@uabc.edu.mx'),('R. Méndez','p2@uabc.edu.mx')");
$bd->exec("INSERT INTO appointment_slots (slot_date) VALUES ('$FECHA 10:00:00'),('$FECHA 11:00:00')");

echo "\nPruebas SIGEP (HU-14 + citas) · PHP " . PHP_VERSION . "\n";

// ------------------------- HU-14 · Registro con Google -------------------------
prueba('C1 · acepta @uabc.edu.mx y deja el registro pendiente', function () {
    $c = cliente(); $r = login($c, 'ana.prueba@uabc.edu.mx');
    eq($r['status'], 200); eq($r['json']['estado'], 'pendiente');
    eq(http($c, 'GET', CUENTA . 'me.php')['json']['estado'], 'pendiente');
});
prueba('C1 · rechaza otros dominios, dominios parecidos y correo no verificado', function () {
    foreach (['x@gmail.com', 'x@uabc.mx', 'x@uabc.edu.mx.evil.com', 'x@evil-uabc.edu.mx', 'x@sub.uabc.edu.mx'] as $e) {
        $r = login(cliente(), $e); eq($r['status'], 403, $e); eq($r['json']['error']['code'], 'DOMINIO_NO_PERMITIDO');
    }
    eq(login(cliente(), 'x@uabc.edu.mx', ['email_verified' => 'false'])['status'], 403, 'no verificado');
});
prueba('C1 · token inválido o emitido para otra aplicación → 401', function () {
    eq(http(cliente(), 'POST', CUENTA . 'auth_google.php', json_encode(['credential' => 'basura']), ['Content-Type: application/json'])['status'], 401, 'basura');
    eq(login(cliente(), 'x@uabc.edu.mx', ['aud' => 'otra-app.apps.googleusercontent.com'])['status'], 401, 'aud');
    eq(login(cliente(), 'x@uabc.edu.mx', ['exp' => '1000'])['status'], 401, 'expirado');
});
prueba('C1 · acceso de pruebas (DEV_MODE) también exige @uabc.edu.mx', function () {
    $h = ['Content-Type: application/json'];
    eq(http(cliente(), 'POST', CUENTA . 'auth_google.php', json_encode(['dev_email' => 'a@gmail.com']), $h)['status'], 403);
    $r = http(cliente(), 'POST', CUENTA . 'auth_google.php', json_encode(['dev_email' => 'dev.uno@uabc.edu.mx']), $h);
    eq($r['status'], 200); eq($r['json']['estado'], 'pendiente'); eq($r['json']['nombre'], 'Dev Uno');
});
prueba('C1 · sin iniciar sesión no se puede registrar', function () use ($bd) {
    $r = registrar(cliente(), valido()); eq($r['status'], 401); eq(cuenta($bd, 'SELECT COUNT(*) FROM students'), 0);
});
prueba('C2 · no guarda si falta cualquier campo obligatorio (y marca cuáles)', function () use ($bd) {
    $c = cliente(); login($c, 'vacio@uabc.edu.mx');
    $r = registrar($c, ['x' => '1']); eq($r['status'], 422);
    foreach (['matricula', 'nombre', 'apellido_paterno', 'sexo', 'telefono', 'programa_id', 'semestre', 'grupo'] as $k) { if (empty($r['json']['error']['campos'][$k])) throw new Exception("falta error en $k"); }
    if (isset($r['json']['error']['campos']['apellido_materno'])) throw new Exception('el apellido materno es opcional');
    eq(cuenta($bd, 'SELECT COUNT(*) FROM students'), 0); eq(cuenta($bd, 'SELECT COUNT(*) FROM users'), 0);
});
prueba('C2 · valida formatos (matrícula, teléfono, semestre, carrera, nombre)', function () use ($bd) {
    $c = cliente(); login($c, 'formato@uabc.edu.mx');
    $r = registrar($c, valido(['matricula' => '12', 'telefono' => '123', 'semestre' => '13', 'programa_id' => '999', 'nombre' => '<b>x</b>']));
    eq($r['status'], 422);
    foreach (['matricula', 'telefono', 'semestre', 'programa_id', 'nombre'] as $k) { if (empty($r['json']['error']['campos'][$k])) throw new Exception($k); }
    eq(cuenta($bd, 'SELECT COUNT(*) FROM students'), 0);
});
prueba('C2 · registro correcto crea usuario + alumno en students; volver a entrar no duplica', function () use ($bd) {
    $c = cliente(); login($c, 'ana.prueba@uabc.edu.mx');
    $r = registrar($c, valido()); eq($r['status'], 201); eq($r['json']['vinculado'], false);
    eq($r['json']['estudiante']['matricula'], '7000001'); eq($r['json']['estudiante']['semestre'], 3);
    eq($r['json']['estudiante']['nombre'], 'Ana Prueba López');
    $s = $bd->query("SELECT * FROM students WHERE student_number='7000001'")->fetch(PDO::FETCH_ASSOC);
    eq($s['registration_source'], 'Autoregistro'); eq($s['institutional_email'], 'ana.prueba@uabc.edu.mx');
    eq($s['first_name'], 'Ana'); eq($s['paternal_last_name'], 'Prueba'); eq($s['maternal_last_name'], 'López'); eq($s['sex'], 'M');
    eq(http($c, 'GET', CUENTA . 'me.php')['json']['estado'], 'registrado');
    eq(login(cliente(), 'ana.prueba@uabc.edu.mx')['json']['estado'], 'registrado');
    eq(cuenta($bd, 'SELECT COUNT(*) FROM users'), 1);
});
prueba('C3 · matrícula creada por la psicóloga con el MISMO correo → se vincula sin revisión', function () use ($bd) {
    $bd->exec("INSERT INTO students (student_number,first_name,paternal_last_name,maternal_last_name,institutional_email,phone,sex,program_id,semester,group_code,registration_source) VALUES ('1234567','Juan','Pérez','Martínez','juan.perez@uabc.edu.mx','6860000000','H',1,5,'551','Psicologa')");
    $antes = cuenta($bd, 'SELECT COUNT(*) FROM students');
    $c = cliente(); login($c, 'juan.perez@uabc.edu.mx');
    $r = registrar($c, valido(['matricula' => '1234567', 'nombre' => 'Juan', 'apellido_paterno' => 'Perez', 'apellido_materno' => '', 'sexo' => 'H', 'semestre' => '6', 'grupo' => '661']));
    eq($r['status'], 201); eq($r['json']['vinculado'], true);
    eq(cuenta($bd, 'SELECT COUNT(*) FROM students'), $antes, 'no debe crear alumno nuevo');
    $e = $bd->query("SELECT * FROM students WHERE student_number='1234567'")->fetch(PDO::FETCH_ASSOC);
    eq($e['registration_source'], 'Psicologa'); eq($e['paternal_last_name'], 'Pérez', 'conserva el nombre de la psicóloga');
    eq((int) $e['link_needs_review'], 0, 'mismo correo verificado por Google'); eq((int) $e['semester'], 6); eq($e['group_code'], '661');
});
prueba('C3 · matrícula creada SIN correo → se vincula, queda por revisar y no revela el nombre guardado', function () use ($bd) {
    $bd->exec("INSERT INTO students (student_number,first_name,paternal_last_name,institutional_email,sex,registration_source) VALUES ('2345678','María','Sánchez',NULL,'M','Psicologa')");
    $c = cliente(); login($c, 'maria.s@uabc.edu.mx');
    $r = registrar($c, valido(['matricula' => '2345678', 'nombre' => 'Mary', 'apellido_paterno' => 'Sanchez', 'apellido_materno' => '']));
    eq($r['status'], 201); eq($r['json']['vinculado'], true);
    $e = $bd->query("SELECT * FROM students WHERE student_number='2345678'")->fetch(PDO::FETCH_ASSOC);
    eq((int) $e['link_needs_review'], 1); eq($e['declared_name'], 'Mary Sanchez'); eq($e['first_name'], 'María');
    eq($e['institutional_email'], 'maria.s@uabc.edu.mx', 'guarda el correo verificado');
    eq($r['json']['estudiante']['nombre'], 'Mary Sanchez', 'no revela el nombre guardado por la psicóloga');
});
prueba('C3 · una matrícula ya vinculada no se puede reclamar otra vez', function () use ($bd) {
    $c = cliente(); login($c, 'intruso@uabc.edu.mx');
    $r = registrar($c, valido(['matricula' => '1234567'])); eq($r['status'], 409); eq($r['json']['error']['code'], 'MATRICULA_VINCULADA');
    eq(cuenta($bd, "SELECT COUNT(*) FROM users WHERE email='intruso@uabc.edu.mx'"), 0, 'transacción revertida');
});
prueba('C4 · acepta PNG y PDF, y el alumno puede ver su horario', function () use ($PNG, $PDF) {
    $c = cliente(); login($c, 'png@uabc.edu.mx');
    $r = registrar($c, valido(['matricula' => '7000002']), $PNG); eq($r['status'], 201); eq($r['json']['estudiante']['tiene_horario'], true);
    $g = http($c, 'GET', CUENTA . 'horario.php'); eq($g['status'], 200); eq($g['raw'], $PNG);
    if (stripos($g['head'], 'content-type: image/png') === false) throw new Exception('content-type');
    $c2 = cliente(); login($c2, 'pdf@uabc.edu.mx');
    eq(registrar($c2, valido(['matricula' => '7000003']), $PDF, 'h.pdf')['status'], 201);
    eq(http(cliente(), 'GET', CUENTA . 'horario.php')['status'], 401, 'sin sesión no se ve');
});
prueba('C4 · rechaza > 5 MB, tipos no permitidos y archivos disfrazados (sin dejar huérfanos)', function () use ($PNG, $bd, $carpetaUp) {
    $antes = count(glob("$carpetaUp/*"));
    $c = cliente(); login($c, 'malo@uabc.edu.mx');
    $r = registrar($c, valido(['matricula' => '7000004']), $PNG . str_repeat("\0", 6 * 1024 * 1024));
    eq($r['status'], 413); eq($r['json']['error']['code'], 'ARCHIVO_GRANDE');
    $r = registrar($c, valido(['matricula' => '7000004']), '<?php echo 1; ?> texto', 'malo.png'); eq($r['status'], 422);
    $r = registrar($c, valido(['matricula' => '7000004']), 'GIF89a......', 'a.gif'); eq($r['status'], 422);
    eq(cuenta($bd, "SELECT COUNT(*) FROM students WHERE student_number='7000004'"), 0, 'nada se guardó');
    eq(count(glob("$carpetaUp/*")), $antes, 'sin archivos huérfanos');
});
prueba('El horario es opcional; config/, database/ y storage/ están bloqueadas por .htaccess', function () use ($raiz) {
    $c = cliente(); login($c, 'sinfoto@uabc.edu.mx');
    $r = registrar($c, valido(['matricula' => '7000005'])); eq($r['status'], 201); eq($r['json']['estudiante']['tiene_horario'], false);
    eq(http($c, 'GET', CUENTA . 'horario.php')['status'], 404);
    foreach (['storage', 'config', 'database'] as $d) { if (!is_file("$raiz/$d/.htaccess")) throw new Exception("falta .htaccess en $d"); }
});
prueba('La interfaz de registro se sirve con rutas relativas', function () {
    $h = http(cliente(), 'GET', 'frontend/registro/index.html'); eq($h['status'], 200);
    if (strpos($h['raw'], 'Crea tu cuenta') === false) throw new Exception('contenido');
    if (preg_match('/(href|src)="\//', $h['raw'])) throw new Exception('hay rutas absolutas que fallan en htdocs/subcarpeta');
    eq(http(cliente(), 'GET', 'frontend/registro/registro.js')['status'], 200); eq(http(cliente(), 'GET', 'frontend/registro/registro.css')['status'], 200);
    $o = http(cliente(), 'GET', CUENTA . 'opciones.php')['json'];
    eq($o['devLogin'], true); eq($o['matriculaDigitos'], MATRICULA_DIGITOS); eq(count($o['programas']), 6);
});

// ------------------------- Integración con citas -------------------------
prueba('Citas · un alumno SIN cuenta puede pedir cita y queda en students como "Cita"', function () use ($bd, $FECHA) {
    $r = pedir_cita(cliente(), alumno_cita(), $FECHA); eq($r['status'], 200, $r['raw']); eq($r['json']['ok'], true);
    eq(cuenta($bd, "SELECT COUNT(*) FROM students WHERE student_number='7100001' AND registration_source='Cita'"), 1);
});
prueba('Citas · la matrícula debe tener los mismos dígitos que en el registro', function () use ($FECHA) {
    $r = pedir_cita(cliente(), alumno_cita(['matricula' => '<svg onload=x>', 'correo_institucional' => 'otro@uabc.edu.mx']), $FECHA);
    eq($r['status'], 422);
});
prueba('Citas · un alumno CON sesión pide cita sin escribir datos (se ignoran los del formulario)', function () use ($bd, $FECHA) {
    $c = cliente(); login($c, 'ana.prueba@uabc.edu.mx');                    // ya registrada arriba (7000001)
    $pagina = http($c, 'GET', 'frontend/citas/index.php');
    if (strpos($pagina['raw'], 'data-sesion="1"') === false || strpos($pagina['raw'], 'Ana Prueba López') === false) throw new Exception('la página no reconoce la sesión');
    if (strpos($pagina['raw'], 'id="alumnoForm"') !== false) throw new Exception('no debería pedir los datos otra vez');
    $r = pedir_cita($c, ['matricula' => 'x', 'nombre' => '', 'apellido_paterno' => '', 'correo_institucional' => 'no'], $FECHA);
    eq($r['status'], 200, $r['raw']);
    $id = (int) $r['json']['id_cita'];
    eq(cuenta($bd, "SELECT COUNT(*) FROM appointments a JOIN students s ON s.student_id=a.student_id WHERE a.appointment_id=? AND s.student_number='7000001'", [$id]), 1);
});
prueba('Citas · si la matrícula ya tiene cuenta, sin sesión se pide iniciar sesión', function () use ($FECHA) {
    $r = pedir_cita(cliente(), alumno_cita(['matricula' => '7000001', 'correo_institucional' => 'ana.prueba@uabc.edu.mx']), $FECHA, '11:00');
    eq($r['status'], 409); if (strpos($r['json']['error'], 'Inicia sesión') === false) throw new Exception($r['json']['error']);
});
prueba('Citas · alumno que pidió cita antes y luego crea su cuenta → se vincula (sin duplicar ni revisión)', function () use ($bd) {
    $c = cliente(); login($c, 'luis.cita@uabc.edu.mx');
    $r = registrar($c, valido(['matricula' => '7100001', 'nombre' => 'Luis', 'apellido_paterno' => 'Cita', 'sexo' => 'H']));
    eq($r['status'], 201); eq($r['json']['vinculado'], true);
    eq(cuenta($bd, "SELECT COUNT(*) FROM students WHERE student_number='7100001' AND link_needs_review=0 AND user_id IS NOT NULL"), 1);
});
prueba('Citas · un correo ya usado con otra matrícula no puede crear un segundo alumno', function () use ($bd, $FECHA) {
    eq(pedir_cita(cliente(), alumno_cita(['matricula' => '7200001', 'correo_institucional' => 'pepe@uabc.edu.mx']), $FECHA, '11:00')['status'], 200);
    $c = cliente(); login($c, 'pepe@uabc.edu.mx');
    $r = registrar($c, valido(['matricula' => '7200002']));
    eq($r['status'], 409); eq($r['json']['error']['code'], 'CORREO_EN_OTRA_MATRICULA');
    eq(cuenta($bd, "SELECT COUNT(*) FROM students WHERE institutional_email='pepe@uabc.edu.mx'"), 1);
});

fin($procs, $tmp, $pdo, $testDb);
echo "\nResultado: $pasaron pasaron, $fallaron fallaron.\n";
exit($fallaron ? 1 : 0);
