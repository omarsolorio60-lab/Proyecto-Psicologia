<?php
// GET: lista de alumnos para la pagina de Estudiantes.
// Cada alumno tiene la misma forma que en mock-estudiantes.js.
// Respuesta: { ok: true, students: [...], psychologists: ["A. Lopez", ...] }

header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/admin_session.php';
requirePsychologist();
require __DIR__ . '/../config/database_connection.php';
// TODO: cuando exista admin_login.php, responder 401 si no hay sesion de psicologa.

$sql = "
SELECT
  s.student_id AS id,
  s.student_number AS enrollment,
  CONCAT_WS(' ', s.first_name, TRIM(s.paternal_last_name), s.maternal_last_name) AS name,
  COALESCE(m.name, s.major, '') AS program,
  s.semester,
  COALESCE(c.priority, 'stable') AS priority,
  COALESCE(c.reason, '') AS reason,
  COALESCE(p.full_name, '') AS psychologist,
  COALESCE(c.status, 'first_attention') AS status,
  COALESCE(c.category, 'academic') AS category,
  (SELECT DATE_FORMAT(MAX(COALESCE(a.confirmed_appointment_date, a.requested_date)), '%Y-%m-%d %H:%i')
     FROM appointments a
    WHERE a.student_id = s.student_id AND a.status = 'Atendida') AS lastSession,
  (SELECT DATE_FORMAT(MIN(COALESCE(a.confirmed_appointment_date, a.requested_date)), '%Y-%m-%d %H:%i')
     FROM appointments a
    WHERE a.student_id = s.student_id
      AND a.status IN ('Solicitada', 'Confirmada')
      AND COALESCE(a.confirmed_appointment_date, a.requested_date) >= NOW()) AS nextAppointment
FROM students s
LEFT JOIN majors m ON m.major_id = s.major_id
LEFT JOIN cases c ON c.case_id = (
  SELECT MAX(c2.case_id) FROM cases c2 WHERE c2.student_id = s.student_id
)
LEFT JOIN psychologists p ON p.psychologist_id = c.psychologist_id
ORDER BY s.student_id
";

try {
    $students = $pdo->query($sql)->fetchAll();

    foreach ($students as &$student) {
        $student['id'] = (int) $student['id'];
        $student['semester'] = $student['semester'] === null ? null : (int) $student['semester'];
    }

    $psychologists = $pdo->query(
        'SELECT full_name FROM psychologists WHERE active = 1 ORDER BY psychologist_id'
    )->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['ok' => true, 'students' => $students, 'psychologists' => $psychologists]);
} catch (PDOException $e) {
    error_log('SIGEP students_list: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo cargar la lista de alumnos.']);
}