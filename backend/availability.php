<?php
date_default_timezone_set('America/Tijuana');
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../config/database_connection.php';

$date = $_GET['fecha'] ?? '';
if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) || !($d = DateTimeImmutable::createFromFormat('!Y-m-d', $date)) || $d->format('Y-m-d') !== $date) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Fecha inválida']);
    exit;
}

try {
    $total = (int) $pdo->query('SELECT COUNT(*) FROM psychologists WHERE active=1')->fetchColumn();

    // Obtener los horarios habilitados en appointment_slots para esa fecha
    $slotStmt = $pdo->prepare("SELECT DATE_FORMAT(slot_date, '%H:%i') FROM appointment_slots WHERE DATE(slot_date) = ?");
    $slotStmt->execute([$date]);
    $enabledSlots = $slotStmt->fetchAll(PDO::FETCH_COLUMN);

    // Conteo de citas ocupadas por bloque horario
    $s = $pdo->prepare("SELECT DATE_FORMAT(requested_date,'%H:%i') AS hour, COUNT(*) AS occupied FROM appointments WHERE DATE(requested_date)=? AND status IN ('Solicitada','Confirmada') GROUP BY DATE_FORMAT(requested_date,'%H:%i')");
    $s->execute([$date]);
    $occupied = $s->fetchAll(PDO::FETCH_KEY_PAIR);

    $slots = [];
    foreach (['09:00', '11:00', '13:00'] as $hour) {
        if (!in_array($hour, $enabledSlots, true)) {
            $slots[$hour] = 0;
        } else {
            $slots[$hour] = max(0, $total - (int) ($occupied[$hour] ?? 0));
        }
    }

    echo json_encode(['ok' => true, 'capacity' => $total, 'available' => $slots]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo consultar la disponibilidad.']);
}
