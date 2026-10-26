<?php
/**
 * Funciones del registro de cuenta del estudiante (HU-14), compatibles con PHP 7.4+.
 * Esta carpeta está bloqueada por .htaccess: no se puede abrir desde el navegador.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Mismos valores que la columna students.sex del esquema.
const SEXOS = [
    ['value' => 'M',  'label' => 'Mujer'],
    ['value' => 'H',  'label' => 'Hombre'],
    ['value' => 'ND', 'label' => 'Prefiero no decirlo'],
];
const TIPOS_HORARIO = [           // tipo real (detectado por contenido) => extensión
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'application/pdf' => 'pdf',
];

/** Error controlado: se convierte en una respuesta JSON con su código HTTP. */
class ApiError extends Exception
{
    public $status;
    public $errorCode;
    public $campos;

    public function __construct(int $status, string $errorCode, string $message, ?array $campos = null)
    {
        parent::__construct($message);
        $this->status    = $status;
        $this->errorCode = $errorCode;
        $this->campos    = $campos;
    }
}

function fallar(int $status, string $codigo, string $mensaje, ?array $campos = null): void
{
    throw new ApiError($status, $codigo, $mensaje, $campos);
}

function responder(array $datos, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}

/** true si quien consulta está en este equipo o en la red local (127.x, 192.168.x, 10.x, ...). */
function es_red_local(): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        return false;
    }
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

function dev_login_permitido(): bool
{
    return DEV_MODE === true && es_red_local();
}

function ini_a_bytes(string $valor): int
{
    $valor = trim($valor);
    $n = (int) $valor;
    switch (strtolower(substr($valor, -1))) {
        case 'g': $n *= 1024; // no hay break a propósito
        case 'm': $n *= 1024;
        case 'k': $n *= 1024;
    }
    return $n;
}

/** Valida un nombre o apellido (letras, espacios, punto, apóstrofo y guion). */
function validar_parte_nombre(string $valor, bool $obligatorio, string $vacio): ?string
{
    if ($valor === '') {
        return $obligatorio ? $vacio : null;
    }
    if (mb_strlen($valor) > 100) {
        return 'Es demasiado largo (máx. 100 caracteres).';
    }
    if (!preg_match('/^\p{L}[\p{L}\p{M}\s.\'’-]*$/u', $valor)) {
        return 'Usa solo letras.';
    }
    return null;
}

/**
 * Criterio 2: todos los campos obligatorios y con formato válido.
 * El nombre se pide en tres partes, igual que la tabla students y el formulario de citas.
 * @return array [errores por campo, valores limpios]
 */
function validar_registro(array $in, array $programaIds): array
{
    $errores = [];
    $v = [];
    $t = static function (string $k) use ($in): string {
        return isset($in[$k]) && is_string($in[$k]) ? trim(preg_replace('/\s+/u', ' ', $in[$k]) ?? '') : '';
    };

    $v['matricula'] = $t('matricula');
    if ($v['matricula'] === '') {
        $errores['matricula'] = 'Escribe tu matrícula.';
    } elseif (!preg_match(sigep_regex_matricula(), $v['matricula'])) {
        $errores['matricula'] = 'La matrícula debe tener ' . MATRICULA_DIGITOS . ' dígitos.';
    }

    $partes = [
        'nombre'           => [true, 'Escribe tu nombre.'],
        'apellido_paterno' => [true, 'Escribe tu apellido paterno.'],
        'apellido_materno' => [false, ''],
    ];
    foreach ($partes as $campo => [$obligatorio, $vacio]) {
        $v[$campo] = $t($campo);
        $error = validar_parte_nombre($v[$campo], $obligatorio, $vacio);
        if ($error !== null) {
            $errores[$campo] = $error;
        }
    }

    $v['sexo'] = $t('sexo');
    if ($v['sexo'] === '') {
        $errores['sexo'] = 'Selecciona una opción.';
    } elseif (!in_array($v['sexo'], array_column(SEXOS, 'value'), true)) {
        $errores['sexo'] = 'Opción no válida.';
    }

    $tel = preg_replace('/[\s().-]/', '', $t('telefono')) ?? '';
    if (preg_match('/^\+?52\d{10}$/', $tel)) {
        $tel = preg_replace('/^\+?52/', '', $tel);
    }
    $v['telefono'] = $tel;
    if ($t('telefono') === '') {
        $errores['telefono'] = 'Escribe tu teléfono.';
    } elseif (!preg_match('/^\d{10}$/', $tel)) {
        $errores['telefono'] = 'El teléfono debe tener 10 dígitos.';
    }

    $v['programa_id'] = (int) $t('programa_id');
    if ($t('programa_id') === '') {
        $errores['programa_id'] = 'Selecciona tu carrera.';
    } elseif (!in_array($v['programa_id'], $programaIds, true)) {
        $errores['programa_id'] = 'Carrera no válida.';
    }

    $v['semestre'] = (int) $t('semestre');
    if ($t('semestre') === '') {
        $errores['semestre'] = 'Selecciona tu semestre.';
    } elseif (!ctype_digit($t('semestre')) || $v['semestre'] < 1 || $v['semestre'] > SEMESTRE_MAX) {
        $errores['semestre'] = 'Semestre no válido.';
    }

    $v['grupo'] = strtoupper($t('grupo'));
    if ($v['grupo'] === '') {
        $errores['grupo'] = 'Escribe tu grupo.';
    } elseif (!preg_match('/^[A-Z0-9-]{1,10}$/', $v['grupo'])) {
        $errores['grupo'] = 'Usa solo letras, números o guion (máx. 10).';
    }

    $v['nombre_completo'] = trim($v['nombre'] . ' ' . $v['apellido_paterno'] . ' ' . $v['apellido_materno']);
    return [$errores, $v];
}

/**
 * Criterio 1 (parte servidor): confirma con Google que el token es auténtico
 * y fue emitido para NUESTRA aplicación. Devuelve los datos de la cuenta.
 */
function verificar_token_google(string $idToken): array
{
    if (!function_exists('curl_init')) {
        fallar(500, 'SIN_CURL', 'Falta la extensión cURL de PHP (actívala en php.ini: extension=curl).');
    }
    $ch = curl_init(GOOGLE_TOKENINFO_URL . '?id_token=' . rawurlencode($idToken));
    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => false,
    ];
    if (CA_BUNDLE !== '') {
        $opciones[CURLOPT_CAINFO] = CA_BUNDLE;
    }
    curl_setopt_array($ch, $opciones);
    $cuerpo = curl_exec($ch);
    $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno  = curl_errno($ch);
    $error  = curl_error($ch);
    curl_close($ch);

    if ($cuerpo === false) {
        error_log("[sigep] cURL ($errno): $error");
        if (in_array($errno, [35, 51, 58, 59, 60, 77], true)) {
            fallar(502, 'SSL_GOOGLE', 'El servidor no pudo validar el certificado de Google. Revisa CA_BUNDLE en config/config.php (ver README).');
        }
        fallar(502, 'GOOGLE_NO_ALCANZABLE', 'El servidor no pudo contactar a Google. Revisa tu conexión a internet.');
    }
    $datos = json_decode((string) $cuerpo, true);
    if ($estado !== 200 || !is_array($datos)) {
        fallar(401, 'GOOGLE_INVALIDO', 'No pudimos verificar tu cuenta de Google. Inténtalo de nuevo.');
    }
    $emisorOk = in_array($datos['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true);
    if (($datos['aud'] ?? '') !== GOOGLE_CLIENT_ID || !$emisorOk || (int) ($datos['exp'] ?? 0) < time()) {
        fallar(401, 'GOOGLE_INVALIDO', 'No pudimos verificar tu cuenta de Google. Inténtalo de nuevo.');
    }
    return [
        'email'          => strtolower(trim((string) ($datos['email'] ?? ''))),
        'email_verified' => ($datos['email_verified'] ?? '') === 'true' || ($datos['email_verified'] ?? false) === true,
        'name'           => (string) ($datos['name'] ?? ''),
        'given_name'     => (string) ($datos['given_name'] ?? ''),
        'sub'            => (string) ($datos['sub'] ?? ''),
    ];
}

/**
 * Criterio 1: solo @uabc.edu.mx. Compara el dominio EXACTO, así
 * "x@uabc.edu.mx.evil.com" o "x@evil-uabc.edu.mx" NO pasan.
 */
function exigir_dominio_uabc(array $cuenta): string
{
    $email   = $cuenta['email'] ?? '';
    $arroba  = strrpos($email, '@');
    $dominio = $arroba === false ? '' : substr($email, $arroba + 1);
    if ($dominio !== strtolower(DOMINIO_PERMITIDO) || ($cuenta['email_verified'] ?? false) !== true) {
        fallar(403, 'DOMINIO_NO_PERMITIDO', 'Solo puedes registrarte con tu cuenta de Google @' . DOMINIO_PERMITIDO . '.');
    }
    return $email;
}

/**
 * Datos del estudiante ligados a una cuenta (lo que se le muestra al propio alumno).
 * Si su vínculo está por revisar, se muestra el nombre que ÉL escribió, nunca el que
 * capturó la psicóloga (así nadie descubre datos de otro reclamando su matrícula).
 */
function vista_estudiante(PDO $pdo, int $usuarioId): ?array
{
    $st = $pdo->prepare(
        "SELECT s.student_number AS matricula,
                CASE WHEN s.link_needs_review = 1 AND s.declared_name IS NOT NULL THEN s.declared_name
                     ELSE CONCAT_WS(' ', s.first_name, s.paternal_last_name, s.maternal_last_name) END AS nombre,
                COALESCE(p.name, s.major) AS programa, s.semester AS semestre, s.group_code AS grupo,
                (s.schedule_file IS NOT NULL) AS tiene_horario
           FROM students s
           LEFT JOIN programs p ON p.program_id = s.program_id
          WHERE s.user_id = ?"
    );
    $st->execute([$usuarioId]);
    $f = $st->fetch();
    if (!$f) {
        return null;
    }
    $f['semestre']      = $f['semestre'] === null ? null : (int) $f['semestre'];
    $f['tiene_horario'] = (bool) (int) $f['tiene_horario'];
    return $f;
}
