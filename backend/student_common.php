<?php
require_once __DIR__ . '/student_common_helpers.php';
function studentFail($message, $code = 422)
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    studentFail('Método no permitido.', 405);
$d = json_decode(file_get_contents('php://input'), true);
if (!is_array($d))
    studentFail('Datos inválidos.');
if (empty($_SESSION['csrf_citas']) || !is_string($d['csrf'] ?? null) || !hash_equals($_SESSION['csrf_citas'], $d['csrf']))
    studentFail('Actualiza la página.', 403);
function emailOk($s)
{
    return is_string($s) && filter_var($s, FILTER_VALIDATE_EMAIL) && preg_match('/^[^\s@]+@uabc\.edu\.mx$/iD', $s);
}
require __DIR__ . '/../config/database_connection.php';
