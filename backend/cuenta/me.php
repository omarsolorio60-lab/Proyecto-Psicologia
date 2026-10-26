<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api_bootstrap.php';

solo_metodo('GET');

$uid = usuario_actual_id();
if ($uid !== null) {
    $pdo = db();
    $st = $pdo->prepare('SELECT email AS correo, role AS rol FROM users WHERE user_id = ? AND active = 1');
    $st->execute([$uid]);
    $u = $st->fetch();
    if ($u) {
        responder([
            'estado'     => 'registrado',
            'usuario'    => $u,
            'estudiante' => vista_estudiante($pdo, $uid),
        ]);
    }
    unset($_SESSION['usuario_id']); // el usuario ya no existe o está desactivado
}

$pend = pendiente_actual();
if ($pend) {
    responder(['estado' => 'pendiente', 'email' => $pend['email'], 'nombre' => $pend['name']]);
}
responder(['estado' => 'anonimo']);
