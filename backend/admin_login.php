<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../frontend/login/access.php'); exit; }
// PENDIENTE: conectar con la tabla real de usuarios administradores.
// Verificar contraseña con password_verify() y guardar sesión SOLO si es válida.
// No se permite entrar al panel administrativo sin autenticación.
http_response_code(501);
?>