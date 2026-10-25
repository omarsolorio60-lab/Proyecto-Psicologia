<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api_bootstrap.php';

solo_metodo('POST');
verificar_origen();
$_SESSION = [];
session_destroy();
responder(['estado' => 'anonimo']);
