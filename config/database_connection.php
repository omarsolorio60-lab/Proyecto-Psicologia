<?php
$host = '127.0.0.1';
$db = 'sigep_db';
$user = 'root';
$password = ''; // Cambia si tu MySQL tiene contraseña.
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('SIGEP DB: ' . $e->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'No se pudo conectar a la base de datos. Comprueba MySQL, nombre de base, puerto y credenciales.']);
    exit;
}
