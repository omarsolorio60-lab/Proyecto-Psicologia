<?php
// Se incluye al inicio de los PHP que solo puede usar una psicologa.
// Si no hay sesion responde 401 y el JS regresa al login.

session_start();

function requirePsychologist()
{
    if (empty($_SESSION['psychologist_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Inicia sesion para continuar.']);
        exit;
    }
    return (int) $_SESSION['psychologist_id'];
}