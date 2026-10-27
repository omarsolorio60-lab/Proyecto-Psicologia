<?php
// GET: catalogo de carreras activas, en el mismo orden que el mock.
// Respuesta: { ok: true, majors: [ { id, name }, ... ] }

header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../config/database_connection.php';

try {
    $rows = $pdo->query(
        'SELECT major_id AS id, name FROM majors WHERE active = 1 ORDER BY major_id'
    )->fetchAll();

    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
    }

    echo json_encode(['ok' => true, 'majors' => $rows]);
} catch (PDOException $e) {
    error_log('SIGEP majors_list: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo cargar el catalogo de carreras.']);
}