<?php
/**
 * SIGEP · Configuración ÚNICA del sistema (citas, registro de estudiantes y panel).
 * Es el único archivo que normalmente necesitas editar.
 * La carpeta config/ está bloqueada por .htaccess: no se puede abrir desde el navegador.
 */

// ---- Zona horaria (las citas se validan con la hora de Tijuana/Mexicali) ----
date_default_timezone_set('America/Tijuana');

// ---- Base de datos (valores por defecto de XAMPP) ----------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'sigep_db');
define('DB_USER', 'root');
define('DB_PASS', '');            // En XAMPP el usuario root viene sin contraseña

// ---- Inicio de sesión con Google (HU-14) -------------------
// Pega aquí tu "ID de cliente" de Google Cloud (ver README).
// Se ve así:  123456789-abcdefg.apps.googleusercontent.com
define('GOOGLE_CLIENT_ID', '26943556139-4m1e4v25eg8u58gt6pa0c18l65reo206.apps.googleusercontent.com');
define('DOMINIO_PERMITIDO', 'uabc.edu.mx');

// ---- Modo pruebas ------------------------------------------
// true  = muestra "Entrar sin Google (pruebas)" en el registro de estudiantes.
//         Solo funciona desde localhost o la red local.
// PONLO EN false ANTES DE PUBLICAR EL SISTEMA.
define('DEV_MODE', false);

// ---- Reglas compartidas por TODOS los formularios -----------
define('MATRICULA_DIGITOS', 7);   // la matrícula UABC son 7 dígitos
define('SEMESTRE_MAX', 12);       // semestre válido: 1 a SEMESTRE_MAX

// ---- Archivo del horario del alumno (HU-14, criterio 4) -----
define('HORARIO_MAX_BYTES', 5 * 1024 * 1024);   // 5 MB
define('HORARIOS_DIR', dirname(__DIR__) . '/storage/horarios');

// ---- Solo si aparece el error "certificado SSL" al usar Google ----
// Ejemplo Windows:  'C:/xampp/php/extras/ssl/cacert.pem'
define('CA_BUNDLE', '');

// No cambiar (solo se redefine en las pruebas automáticas).
define('GOOGLE_TOKENINFO_URL', getenv('SIGEP_TOKENINFO_URL') ?: 'https://oauth2.googleapis.com/tokeninfo');

/**
 * Sesión ÚNICA para todo el sistema: la usan las citas, el registro con Google
 * y (más adelante) el panel administrativo. Así, un alumno que inició sesión
 * puede pedir cita sin volver a escribir sus datos.
 */
function sigep_iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('sigep_sess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/** Expresión regular de matrícula válida (para PHP). */
function sigep_regex_matricula(): string
{
    return '/^\d{' . MATRICULA_DIGITOS . '}$/D';
}
