<?php
// GET: datos del panel de inicio de la psicologa (HU-15).
// Respuesta: { ok, staff, otherStaff, stats, appointments, cases }

header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/admin_session.php';
$me = requirePsychologist();
require __DIR__ . '/../config/database_connection.php';

// "Juan Perez Martinez" -> "J.P.M" (los casos solo se muestran con iniciales)
function initials($fullName)
{
    $letters = [];
    foreach (preg_split('/\s+/', trim($fullName)) as $word) {
        if ($word !== '') {
            $letters[] = mb_strtoupper(mb_substr($word, 0, 1));
        }
    }
    return implode('.', $letters);
}

try {
    

    $q = $pdo->prepare('SELECT psychologist_id AS id, full_name AS name FROM psychologists WHERE psychologist_id = ?');
    $q->execute([$me]);
    $staff = $q->fetch();
    if (!$staff) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'No hay psicologas registradas.']);
        exit;
    }
    $staff['id'] = (int) $staff['id'];

    $q = $pdo->prepare('SELECT psychologist_id AS id, full_name AS name FROM psychologists WHERE active = 1 AND psychologist_id <> ? ORDER BY psychologist_id');
    $q->execute([$me]);
    $otherStaff = $q->fetchAll();
    foreach ($otherStaff as &$person) {
        $person['id'] = (int) $person['id'];
    }
    unset($person);

    // Citas de hoy (criterio 2)
    $q = $pdo->prepare("
        SELECT
          DATE_FORMAT(COALESCE(a.confirmed_appointment_date, a.requested_date), '%H:%i') AS time,
          CONCAT_WS(' ', s.first_name, TRIM(s.paternal_last_name), s.maternal_last_name) AS name,
          s.student_number AS enrollment,
          COALESCE(m.name, s.major, '') AS program,
          COALESCE(a.student_reason, '') AS reason,
          CASE a.appointment_mode WHEN 'Virtual' THEN 'online' ELSE 'in_person' END AS mode
        FROM appointments a
        JOIN students s ON s.student_id = a.student_id
        LEFT JOIN majors m ON m.major_id = s.major_id
        WHERE a.psychologist_id = ?
          AND a.status IN ('Solicitada', 'Confirmada', 'Atendida')
          AND DATE(COALESCE(a.confirmed_appointment_date, a.requested_date)) = CURDATE()
        ORDER BY time
    ");
    $q->execute([$me]);
    $appointments = $q->fetchAll();

    $inPerson = 0;
    foreach ($appointments as $appointment) {
        if ($appointment['mode'] === 'in_person') {
            $inPerson++;
        }
    }

    // Casos prioritarios: solo iniciales y matricula (criterio 3)
    $q = $pdo->prepare("
        SELECT
          c.case_id AS caseId,
          CONCAT_WS(' ', s.first_name, TRIM(s.paternal_last_name), s.maternal_last_name) AS fullName,
          s.student_number AS enrollment,
          c.reason,
          c.priority,
          (SELECT DATE_FORMAT(MAX(COALESCE(a.confirmed_appointment_date, a.requested_date)), '%Y-%m-%d %H:%i')
             FROM appointments a
            WHERE a.student_id = c.student_id AND a.status = 'Atendida') AS lastSession
        FROM cases c
        JOIN students s ON s.student_id = c.student_id
        WHERE c.psychologist_id = ?
          AND c.status <> 'closed'
          AND c.priority IN ('high', 'medium')
        ORDER BY FIELD(c.priority, 'high', 'medium'), c.updated_at DESC
        LIMIT 5
    ");
    $q->execute([$me]);
    $cases = [];
    foreach ($q->fetchAll() as $row) {
        $cases[] = [
            'caseId' => (int) $row['caseId'],
            'initials' => initials($row['fullName']),
            'enrollment' => $row['enrollment'],
            'reason' => $row['reason'],
            'priority' => $row['priority'],
            'lastSession' => $row['lastSession'],
        ];
    }

    // Tarjetas (criterio 1)
    $q = $pdo->prepare("
        SELECT
          COUNT(*) AS active,
          SUM(priority = 'high') AS high
        FROM cases
        WHERE psychologist_id = ? AND status <> 'closed'
    ");
    $q->execute([$me]);
    $counts = $q->fetch();

    echo json_encode([
        'ok' => true,
        'staff' => $staff,
        'otherStaff' => $otherStaff,
        'stats' => [
            'appointmentsToday' => count($appointments),
            'inPerson' => $inPerson,
            'online' => count($appointments) - $inPerson,
            'activeStudents' => (int) $counts['active'],
            'priorityCases' => (int) $counts['high'],
            'pendingExcuses' => 0, // TODO: no existe tabla de justificantes todavia
        ],
        'appointments' => $appointments,
        'cases' => $cases,
    ]);
} catch (PDOException $e) {
    error_log('SIGEP dashboard: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo cargar el panel.']);
}