<?php
// POST: registrar un alumno desde la pagina de Estudiantes (HU-02).
// Recibe: { enrollment, fullName, email, phone, program, semester, reason, psychologist }
//   reason = academic | personal | vocational | excuse
//   psychologist = nombre de la psicologa (como viene en students_list.php)
// Guarda el alumno en students y le abre un caso en cases.
// Respuesta: { ok: true, id: 35 }

header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/admin_session.php';
requirePsychologist();

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
if (!is_array($data)) {
    fail('Datos invalidos.');
}

$enrollment = trim((string) ($data['enrollment'] ?? ''));
$fullName = trim(preg_replace('/\s+/u', ' ', (string) ($data['fullName'] ?? '')));
$email = strtolower(trim((string) ($data['email'] ?? '')));
$phone = trim((string) ($data['phone'] ?? ''));
$program = trim((string) ($data['program'] ?? ''));
$semester = (int) ($data['semester'] ?? 0);
$reason = (string) ($data['reason'] ?? '');
$psychologist = trim((string) ($data['psychologist'] ?? ''));

// Misma regla que shared/validation.js: 6 a 9 caracteres, numeros, _ y -
if (!preg_match('/^\d[\d_-]{5,8}$/', $enrollment)) {
    fail('La matricula no es valida.');
}
if (!preg_match('/^[^\s@]+@uabc\.edu\.mx$/D', $email) || strlen($email) > 150) {
    fail('El correo debe ser institucional (@uabc.edu.mx).');
}
if (!preg_match('/^\d{10}$/D', $phone)) {
    fail('El telefono debe tener 10 digitos.');
}
if ($semester < 1 || $semester > 12) {
    fail('El semestre no es valido.');
}
if (!in_array($reason, ['academic', 'personal', 'vocational', 'excuse'], true)) {
    fail('El motivo no es valido.');
}

// Nombre completo -> nombre(s), apellido paterno y apellido materno.
// Las dos ultimas palabras son los apellidos; con dos palabras no hay materno.
$words = explode(' ', $fullName);
if (count($words) < 2) {
    fail('Escribe el nombre y al menos un apellido.');
}
if (count($words) === 2) {
    [$firstName, $paternal] = $words;
    $maternal = null;
} else {
    $maternal = array_pop($words);
    $paternal = array_pop($words);
    $firstName = implode(' ', $words);
}

require __DIR__ . '/../config/database_connection.php';

try {
    $q = $pdo->prepare('SELECT major_id FROM majors WHERE name = ? AND active = 1');
    $q->execute([$program]);
    $majorId = $q->fetchColumn();
    if ($majorId === false) {
        fail('La carrera seleccionada no existe.');
    }

    $q = $pdo->prepare('SELECT psychologist_id FROM psychologists WHERE full_name = ? AND active = 1');
    $q->execute([$psychologist]);
    $psychologistId = $q->fetchColumn();
    if ($psychologistId === false) {
        fail('La psicologa seleccionada no existe.');
    }

    $q = $pdo->prepare('SELECT student_number, institutional_email FROM students WHERE student_number = ? OR institutional_email = ?');
    $q->execute([$enrollment, $email]);
    $existing = $q->fetch();
    if ($existing) {
        fail($existing['student_number'] === $enrollment
            ? 'La matricula ya esta registrada.'
            : 'El correo ya esta registrado con otra matricula.', 409);
    }

    $pdo->beginTransaction();

    $q = $pdo->prepare('
        INSERT INTO students (student_number, first_name, paternal_last_name, maternal_last_name, institutional_email, phone, major, major_id, semester)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $q->execute([$enrollment, $firstName, $paternal, $maternal, $email, $phone, $program, (int) $majorId, $semester]);
    $studentId = (int) $pdo->lastInsertId();

    // Mismo criterio que la pagina: solo el motivo academico es categoria academica
    $category = $reason === 'academic' ? 'academic' : 'emotional';
    $q = $pdo->prepare("
        INSERT INTO cases (student_id, psychologist_id, status, priority, category, reason)
        VALUES (?, ?, 'first_attention', 'stable', ?, ?)
    ");
    $q->execute([$studentId, (int) $psychologistId, $category, $reason]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'id' => $studentId]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('SIGEP create_student: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el alumno.']);
}
