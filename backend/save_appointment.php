<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
function fail($message, $status = 422)
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}
ini_set('display_errors', '0');
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    fail('Método no permitido.', 405);
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data))
    fail('Datos inválidos.', 400);
if (empty($_SESSION['csrf_citas']) || !is_string($data['csrf'] ?? null) || !hash_equals($_SESSION['csrf_citas'], $data['csrf']))
    fail('La sesión expiró. Actualiza la página.', 403);
if (empty($_SESSION['student_id']))
    fail('Primero crea o verifica tu cuenta.', 403);
$a = $data['alumno'] ?? null;
if (!is_array($a))
    fail('Completa tus datos.');
$fields = ['matricula' => 20, 'nombre' => 100, 'apellido_paterno' => 100, 'apellido_materno' => 100, 'correo_institucional' => 150, 'telefono' => 20, 'carrera' => 120];
foreach ($fields as $field => $max) {
    if (!is_string($a[$field] ?? null))
        fail('Datos del alumno inválidos.');
    $a[$field] = trim($a[$field]);
    if (strlen($a[$field]) > $max)
        fail('Un campo supera el límite permitido.');
}
require_once __DIR__ . '/student_common_helpers.php';
if ($e = matriculaError($a['matricula']))
    fail($e);
if ($e = nameError($a['nombre'], 'El nombre'))
    fail($e);
if ($e = nameError($a['apellido_paterno'], 'El apellido paterno'))
    fail($e);
if (!filter_var($a['correo_institucional'], FILTER_VALIDATE_EMAIL) || !preg_match('/@uabc\.edu\.mx$/i', $a['correo_institucional']))
    fail('Utiliza un correo institucional UABC válido.');
$semestre = $a['semestre'] ?? '';
if (!is_string($semestre))
    fail('Semestre inválido.');
$semestre = trim($semestre);
if ($semestre !== '' && (!ctype_digit($semestre) || (int) $semestre < 1 || (int) $semestre > 20))
    fail('El semestre debe estar entre 1 y 20.');
$fecha = $data['fecha'] ?? '';
$hora = $data['hora'] ?? '';
$modalidad = $data['modalidad'] ?? '';
$motivo = $data['motivo'] ?? '';
$detalle = $data['detalle'] ?? '';
if (!is_string($fecha) || !is_string($hora) || !is_string($modalidad) || !is_string($motivo) || !is_string($detalle))
    fail('Datos de cita inválidos.');
$detalle = trim($detalle);
$dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$fecha $hora");
$validDate = $dt && $dt->format('Y-m-d H:i') === "$fecha $hora" && $dt > new DateTimeImmutable('now');
$validHour = (bool) preg_match('/^(0[8-9]|1[0-5]):(00|30)$|^16:00$/D', $hora);
if (!$validDate || !$validHour || !in_array($modalidad, ['Presencial', 'Virtual'], true) || !in_array($motivo, ['Académico', 'Personal o emocional', 'Orientación vocacional', 'Justificante', 'Otro'], true) || strlen($detalle) > 180)
    fail('Revisa el motivo, modalidad, fecha y horario.');
$texto = $motivo . ($detalle !== '' ? ' - ' . $detalle : '');
if (strlen($texto) > 255)
    fail('El motivo es demasiado largo.');
try {
    require __DIR__ . '/../config/database_connection.php';
    $pdo->beginTransaction();
    // Serializa las reservas del mismo horario para impedir sobrecupos concurrentes.
    // El bloqueo se mantiene hasta el COMMIT/ROLLBACK.
    $lock = $pdo->prepare('SELECT slot_date FROM appointment_slots WHERE slot_date=? FOR UPDATE');
    $lock->execute([$dt->format('Y-m-d H:i:s')]);
    if (!$lock->fetchColumn()) {
        $pdo->rollBack();
        fail('Horario no habilitado. Selecciona otro horario.', 409);
    }
    $psych = $pdo->query('SELECT psychologist_id FROM psychologists WHERE active=1 ORDER BY psychologist_id FOR UPDATE')->fetchAll(PDO::FETCH_COLUMN);
    if (!$psych) {
        $pdo->rollBack();
        fail('No hay psicólogas disponibles.', 409);
    }
    $occupied = $pdo->prepare("SELECT psychologist_id FROM appointments WHERE requested_date=? AND status IN ('Solicitada','Confirmada')");
    $occupied->execute([$dt->format('Y-m-d H:i:s')]);
    $busy = array_map('intval', $occupied->fetchAll(PDO::FETCH_COLUMN));
    $assigned = null;
    foreach ($psych as $id) {
        if (!in_array((int) $id, $busy, true)) {
            $assigned = (int) $id;
            break;
        }
    }
    if ($assigned === null) {
        $pdo->rollBack();
        fail('Este horario ya está completo. Selecciona otra hora.', 409);
    }
    // Bloquea el registro del estudiante mientras se determina si ya existe.
    $stmt = $pdo->prepare('SELECT student_id, institutional_email FROM students WHERE student_id = ? AND student_number = ? FOR UPDATE');
    $stmt->execute([$_SESSION['student_id'], $a['matricula']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        if (strcasecmp(trim($existing['institutional_email']), $a['correo_institucional']) !== 0) {
            $pdo->rollBack();
            fail('La matrícula ya está registrada con otro correo institucional. Verifica tus datos o solicita ayuda a administración.', 409);
        }
        $alumnoId = (int) $existing['student_id'];
    } else {
        $pdo->rollBack();
        fail('Tu cuenta no coincide. Vuelve a verificarla.', 403);
        // Evita vincular una matrícula nueva al correo de otro estudiante.
        $stmt = $pdo->prepare('SELECT student_id FROM students WHERE institutional_email = ? LIMIT 1');
        $stmt->execute([$a['correo_institucional']]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            fail('Este correo institucional ya pertenece a otra matrícula. Verifica tus datos.', 409);
        }
        $stmt = $pdo->prepare('INSERT INTO students (student_number,first_name,paternal_last_name,maternal_last_name,institutional_email,phone,major,semester) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$a['matricula'], $a['nombre'], $a['apellido_paterno'], $a['apellido_materno'] ?: null, $a['correo_institucional'], $a['telefono'] ?: null, $a['carrera'] ?: null, $semestre === '' ? null : (int) $semestre]);
        $alumnoId = (int) $pdo->lastInsertId();
    }
    $stmt = $pdo->prepare("INSERT INTO appointments (student_id,psychologist_id,requested_date,appointment_mode,student_reason,status,registration_source) VALUES (?,?,?,?,?,'Solicitada','Alumno')");
    $stmt->execute([$alumnoId, $assigned, $dt->format('Y-m-d H:i:s'), $modalidad, $texto]);
    $citaId = (int) $pdo->lastInsertId();
    $pdo->commit();
    unset($_SESSION['csrf_citas']);
    echo json_encode(['ok' => true, 'id_cita' => $citaId]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction())
        $pdo->rollBack();
    error_log('SIGEP registro: ' . $e->getMessage());
    if ($e->getCode() === '23000')
        fail('La matrícula o el correo presentan un conflicto. Verifica que correspondan al mismo estudiante y vuelve a intentar.', 409);
    fail('No se pudo registrar la solicitud. Revisa el registro de errores de PHP.', 500);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction())
        $pdo->rollBack();
    error_log('SIGEP registro: ' . $e->getMessage());
    fail('Ocurrió un error del servidor. Revisa el registro de PHP.', 500);
}
