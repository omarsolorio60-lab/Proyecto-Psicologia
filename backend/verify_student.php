<?php
require __DIR__ . '/student_common.php';
$m = trim($d['matricula'] ?? '');
$e = strtolower(trim($d['correo_institucional'] ?? ''));
$mErr = preg_match('/\p{L}|[^0-9_-]/u', $m) || $m === '' || strlen($m) > 9 ? matriculaError($m) : '';
if ($mErr)
    studentFail($mErr);
if (!emailOk($e))
    studentFail('El correo institucional debe ser un correo válido @uabc.edu.mx.');
$q = $pdo->prepare('SELECT * FROM students WHERE student_number=? AND institutional_email=? LIMIT 1');
$q->execute([$m, $e]);
$row = $q->fetch();
if (!$row)
    studentFail('No encontramos una cuenta con esa matrícula y correo.', 404);
$_SESSION['student_id'] = (int) $row['student_id'];
$student = ['matricula' => $row['student_number'], 'nombre' => $row['first_name'], 'apellido_paterno' => $row['paternal_last_name'], 'apellido_materno' => $row['maternal_last_name'] ?? '', 'correo_institucional' => $row['institutional_email'], 'telefono' => $row['phone'] ?? '', 'carrera' => $row['major'] ?? '', 'semestre' => (string) ($row['semester'] ?? '')];
echo json_encode(['ok' => true, 'student' => $student]);
