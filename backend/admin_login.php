<?php
require_once __DIR__.'/../config/config.php';
sigep_iniciar_sesion();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../frontend/login/access.php'); exit; }
// PENDIENTE: usar la tabla users (role = 'administrador' o 'psicologa', columna password_hash).
// Verificar contraseña con password_verify() y guardar sesión SOLO si es válida.
// No se permite entrar al panel administrativo sin autenticación.
http_response_code(501);
?>