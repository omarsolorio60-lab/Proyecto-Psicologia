<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos.']);
    exit;
}

$nombre = trim($data['nombre'] ?? '');
$preparatoria = trim($data['preparatoria'] ?? '');
$correo = trim($data['correo'] ?? '');
$carrera = trim($data['carrera'] ?? '');
$privacidad = !empty($data['privacy']);

if ($nombre === '' || mb_strlen($nombre) > 150) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'El nombre completo es requerido (máx. 150 caracteres).']);
    exit;
}
if ($preparatoria === '' || mb_strlen($preparatoria) > 150) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'La preparatoria de procedencia es requerida (máx. 150 caracteres).']);
    exit;
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Correo electrónico inválido.']);
    exit;
}
if ($carrera === '' || mb_strlen($carrera) > 120) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Selecciona una carrera de interés.']);
    exit;
}
if (!$privacidad) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Debes aceptar el aviso de privacidad.']);
    exit;
}

try {
    require __DIR__ . '/../config/database_connection.php';

    $stmt = $pdo->prepare(
        "INSERT INTO applicants (full_name, high_school, email, career_interest, privacy_accepted)
         VALUES (?, ?, ?, ?, 1)"
    );
    $stmt->execute([$nombre, $preparatoria, $correo, $carrera]);

    echo json_encode([
        'ok' => true,
        'id' => (int) $pdo->lastInsertId(),
        'msg' => '¡Gracias por registrarte! Te enviaremos información pronto.'
    ]);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'Este correo ya está registrado.']);
        exit;
    }
    error_log('SIGEP aspirante: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al guardar en la base de datos.']);
} catch (Throwable $e) {
    error_log('SIGEP aspirante: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor.']);
}
?>