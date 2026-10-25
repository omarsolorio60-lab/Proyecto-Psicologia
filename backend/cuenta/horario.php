<?php
/** El alumno puede ver SU propio horario. (La vista de la psicóloga llegará en otra historia.) */
declare(strict_types=1);
require_once __DIR__ . '/../../config/api_bootstrap.php';

solo_metodo('GET');
$uid = usuario_actual_id();
if ($uid === null) {
    fallar(401, 'NO_AUTENTICADO', 'Inicia sesión para continuar.');
}
$st = db()->prepare('SELECT schedule_file, schedule_mime FROM students WHERE user_id = ?');
$st->execute([$uid]);
$f = $st->fetch();
if (!$f || !$f['schedule_file']) {
    fallar(404, 'SIN_HORARIO', 'Aún no has subido tu horario.');
}
$ruta = HORARIOS_DIR . '/' . basename((string) $f['schedule_file']);
if (!is_file($ruta)) {
    fallar(404, 'SIN_HORARIO', 'No se encontró el archivo del horario.');
}
header('Content-Type: ' . $f['schedule_mime']);
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: inline');
header('Cache-Control: private, no-store');
readfile($ruta);
exit;
