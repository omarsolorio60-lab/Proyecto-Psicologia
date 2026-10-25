<?php
/**
 * Criterio 1: inicio de sesión con Google (solo @uabc.edu.mx).
 * Recibe JSON:  {"credential": "<token de Google>"}     (normal)
 *          o:   {"dev_email": "correo@uabc.edu.mx"}     (solo DEV_MODE y red local)
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/api_bootstrap.php';

solo_metodo('POST');
verificar_origen();
$in = leer_json();

if (isset($in['dev_email'])) {
    if (!dev_login_permitido()) {
        fallar(403, 'DEV_DESHABILITADO', 'El acceso de pruebas está deshabilitado.');
    }
    $email = strtolower(trim((string) $in['dev_email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fallar(422, 'CORREO_INVALIDO', 'Escribe un correo válido.');
    }
    $local  = explode('@', $email)[0];
    $nombre = ucwords(trim(preg_replace('/[._]+/', ' ', $local) ?? ''));
    $cuenta = ['email' => $email, 'email_verified' => true, 'name' => $nombre, 'given_name' => '', 'sub' => ''];
} else {
    $credencial = $in['credential'] ?? null;
    if (!is_string($credencial) || $credencial === '') {
        fallar(400, 'CREDENCIAL_FALTANTE', 'Falta la credencial de Google.');
    }
    if (GOOGLE_CLIENT_ID === '') {
        fallar(500, 'GOOGLE_SIN_CONFIGURAR', 'El acceso con Google no está configurado (falta GOOGLE_CLIENT_ID en config/config.php).');
    }
    $cuenta = verificar_token_google($credencial);
}

$email = exigir_dominio_uabc($cuenta); // lanza 403 si no es @uabc.edu.mx

$pdo = db();
$st = $pdo->prepare('SELECT user_id, active FROM users WHERE email = ?');
$st->execute([$email]);
$existente = $st->fetch();

if ($existente && (int) $existente['active'] !== 1) {
    fallar(403, 'CUENTA_DESACTIVADA', 'Tu cuenta está desactivada. Acude al área psicopedagógica.');
}

session_regenerate_id(true);
if ($existente) {
    unset($_SESSION['pending']);
    $_SESSION['usuario_id'] = (int) $existente['user_id'];
    responder(['estado' => 'registrado']);
}

unset($_SESSION['usuario_id']);
$_SESSION['pending'] = ['email' => $email, 'sub' => $cuenta['sub'], 'name' => $cuenta['given_name'] ?: $cuenta['name'], 'at' => time()];
responder(['estado' => 'pendiente', 'email' => $email, 'nombre' => $_SESSION['pending']['name']]);
