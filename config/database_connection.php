<?php
// Conexión usada por backend/availability.php y backend/save_appointment.php.
// Los datos de conexión están en config/config.php (un solo lugar para todo el sistema).
require_once __DIR__ . '/config.php';
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME), DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('SIGEP DB: ' . $e->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'No se pudo conectar a la base de datos. Comprueba MySQL, nombre de base, puerto y credenciales en config/config.php.']);
    exit;
}
