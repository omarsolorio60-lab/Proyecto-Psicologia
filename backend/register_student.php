<?php
require __DIR__ . '/student_common.php';
$labels = ['matricula' => 'La matrícula', 'nombre' => 'El nombre', 'apellido_paterno' => 'El apellido paterno', 'apellido_materno' => 'El apellido materno', 'correo_institucional' => 'El correo institucional', 'telefono' => 'El teléfono', 'carrera' => 'La carrera', 'semestre' => 'El semestre'];
$max = ['matricula' => 20, 'nombre' => 100, 'apellido_paterno' => 100, 'apellido_materno' => 100, 'correo_institucional' => 150, 'telefono' => 20, 'carrera' => 120, 'semestre' => 3];
// Todos los campos son obligatorios
foreach ($labels as $k => $label) {
    $v = $d[$k] ?? null;
    if (is_int($v))
        $v = (string) $v;
    if (!is_string($v) || trim($v) === '')
        studentFail("$label es obligatorio.");
    $d[$k] = trim($v);
    if (strlen($d[$k]) > $max[$k])
        studentFail("$label supera el límite de caracteres permitido.");
}
if ($e = matriculaError($d['matricula']))
    studentFail($e);
if ($e = nameError($d['nombre'], 'El nombre'))
    studentFail($e);
if ($e = nameError($d['apellido_paterno'], 'El apellido paterno'))
    studentFail($e);
if ($e = nameError($d['apellido_materno'], 'El apellido materno'))
    studentFail($e);
$d['correo_institucional'] = strtolower($d['correo_institucional']);
if (!emailOk($d['correo_institucional']))
    studentFail('El correo institucional debe ser un correo válido @uabc.edu.mx.');
if (!preg_match('/^\d{10}$/D', $d['telefono']))
    studentFail('El teléfono debe tener 10 dígitos y solo números.');
$sem = $d['semestre'];
if (!ctype_digit($sem) || (int) $sem < 1 || (int) $sem > 20)
    studentFail('El semestre debe ser un número entre 1 y 20.');
try {
    $q = $pdo->prepare('INSERT INTO students (student_number,first_name,paternal_last_name,maternal_last_name,institutional_email,phone,major,semester) VALUES (?,?,?,?,?,?,?,?)');
    $q->execute([$d['matricula'], $d['nombre'], $d['apellido_paterno'], $d['apellido_materno'], $d['correo_institucional'], $d['telefono'], $d['carrera'], (int) $sem]);
    $_SESSION['student_id'] = (int) $pdo->lastInsertId();
    echo json_encode(['ok' => true, 'student' => array_intersect_key($d, array_flip(['matricula', 'nombre', 'apellido_paterno', 'apellido_materno', 'correo_institucional', 'telefono', 'carrera', 'semestre']))]);
} catch (PDOException $ex) {
    if ($ex->getCode() === '23000')
        studentFail('Esta matrícula o correo ya está registrado. Usa «Ya tengo una cuenta».', 409);
    error_log($ex->getMessage());
    studentFail('Error al crear la cuenta.', 500);
}
