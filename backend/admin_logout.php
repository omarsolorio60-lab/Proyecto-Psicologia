<?php
// Cierra la sesion de la psicologa y regresa a la pantalla de acceso.

session_start();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();
header('Location: ../frontend/login/access.php');
exit;