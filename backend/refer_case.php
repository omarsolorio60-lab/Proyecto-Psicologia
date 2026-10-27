<?php
// POST: canalizar un caso a otra psicologa (HU-15, criterio 4).
// Recibe: { caseId, toPsychologistId }
// Respuesta: { ok: true, to: "R. Mendez" }

header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/admin_session.php';
$me = requirePsychologist(); 

function fail($message, $code = 422)
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Metodo no permitido.', 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$caseId = isset($data['caseId']) ? (int) $data['caseId'] : 0;
$toId = isset($data['toPsychologistId']) ? (int) $data['toPsychologistId'] : 0;
if ($caseId <= 0 || $toId <= 0) {
    fail('Datos invalidos.');
}

require __DIR__ . '/../config/database_connection.php';


if ($toId === $me) {
    fail('El caso ya es tuyo.');
}

try {
    $q = $pdo->prepare('SELECT full_name FROM psychologists WHERE psychologist_id = ? AND active = 1');
    $q->execute([$toId]);
    $toName = $q->fetchColumn();
    if ($toName === false) {
        fail('La psicologa seleccionada no existe.');
    }

    // Solo se puede canalizar un caso propio que no este cerrado
    $q = $pdo->prepare("
        UPDATE cases
           SET psychologist_id = ?, status = 'referred'
         WHERE case_id = ? AND psychologist_id = ? AND status <> 'closed'
    ");
    $q->execute([$toId, $caseId, $me]);

    if ($q->rowCount() === 0) {
        fail('No se encontro el caso o no te pertenece.', 404);
    }

    echo json_encode(['ok' => true, 'to' => $toName]);
} catch (PDOException $e) {
    error_log('SIGEP refer_case: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo canalizar el caso.']);
}