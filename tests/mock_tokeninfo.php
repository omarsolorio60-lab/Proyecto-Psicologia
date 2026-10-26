<?php
// Simula https://oauth2.googleapis.com/tokeninfo SOLO para las pruebas automáticas.
// El "token" es un JSON en base64 con los datos de la cuenta.
header('Content-Type: application/json');
$p = json_decode((string) base64_decode($_GET['id_token'] ?? '', true), true);
if (!is_array($p)) { http_response_code(400); echo '{"error":"invalid_token"}'; return; }
echo json_encode(array_merge([
    'aud' => 'test-client.apps.googleusercontent.com', 'iss' => 'https://accounts.google.com',
    'exp' => (string) (time() + 3600), 'email_verified' => 'true', 'sub' => '1' . random_int(1000, 9999), 'name' => 'Ana Prueba López',
], $p));
