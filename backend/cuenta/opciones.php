<?php
/** Catálogos y opciones que necesita el formulario de registro (frontend/registro). */
declare(strict_types=1);
require_once __DIR__ . '/../../config/api_bootstrap.php';

solo_metodo('GET');

$programas = db()->query('SELECT program_id AS id, name AS nombre FROM programs WHERE active = 1 ORDER BY program_id')->fetchAll();
foreach ($programas as &$p) {
    $p['id'] = (int) $p['id'];
}
unset($p);

responder([
    'googleClientId'   => GOOGLE_CLIENT_ID !== '' ? GOOGLE_CLIENT_ID : null,
    'devLogin'         => dev_login_permitido(),
    'dominio'          => DOMINIO_PERMITIDO,
    'sexos'            => SEXOS,
    'semestreMax'      => SEMESTRE_MAX,
    'matriculaDigitos' => MATRICULA_DIGITOS,
    'maxHorarioMB'     => HORARIO_MAX_BYTES / 1024 / 1024,
    'programas'        => $programas,
]);
