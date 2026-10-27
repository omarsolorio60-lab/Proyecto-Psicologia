<?php
// Inicio de sesion de la psicologa (administrador).
// Recibe el formulario de frontend/login/access.php por POST.
// Si los datos son correctos guarda la sesion y manda al panel de inicio.

session_start();

$loginPage = '../frontend/login/access.php';
$homePage = '../frontend/inicio/inicio.html';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $loginPage);
    exit;
}

$email = strtolower(trim($_POST['correo'] ?? ''));
// No usar el nombre password: database_connection.php ya lo usa para MySQL
$typedPassword = $_POST['contrasena'] ?? '';

// Correo con formato valido de UABC y contrasena no vacia
if (!preg_match('/^[^\s@]+@uabc\.edu\.mx$/D', $email) || $typedPassword === '') {
    header('Location: ' . $loginPage . '?error=datos');
    exit;
}

require __DIR__ . '/../config/database_connection.php';

try {
    $q = $pdo->prepare(
        'SELECT psychologist_id, full_name, password_hash
           FROM psychologists
          WHERE institutional_email = ? AND active = 1
          LIMIT 1'
    );
    $q->execute([$email]);
    $user = $q->fetch();
} catch (PDOException $e) {
    error_log('SIGEP admin_login: ' . $e->getMessage());
    header('Location: ' . $loginPage . '?error=servidor');
    exit;
}

// Mismo mensaje si no existe el correo o si la contrasena esta mal
if (!$user || empty($user['password_hash']) || !password_verify($typedPassword, $user['password_hash'])) {
    header('Location: ' . $loginPage . '?error=credenciales');
    exit;
}

// Nueva sesion para evitar que alguien reutilice una sesion anterior
session_regenerate_id(true);
$_SESSION['psychologist_id'] = (int) $user['psychologist_id'];
$_SESSION['psychologist_name'] = $user['full_name'];
$_SESSION['role'] = 'admin';

header('Location: ' . $homePage);
exit;