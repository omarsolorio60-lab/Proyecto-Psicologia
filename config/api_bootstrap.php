<?php
/**
 * Arranque común de backend/cuenta/*.php: respuestas JSON, sesión, errores y base de datos.
 */
declare(strict_types=1);

require_once __DIR__ . '/cuenta_helpers.php';

// Los avisos de PHP NUNCA deben mezclarse con el JSON (rompen la pantalla). Van al log.
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

set_exception_handler(static function (Throwable $e): void {
    if ($e instanceof ApiError) {
        $err = ['code' => $e->errorCode, 'message' => $e->getMessage()];
        if ($e->campos) {
            $err['campos'] = $e->campos;
        }
        responder(['error' => $err], $e->status);
    }
    error_log('[sigep] ' . get_class($e) . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if ($e instanceof PDOException && in_array((string) $e->getCode(), ['42S02', '42S22'], true)) {
        responder(['error' => ['code' => 'BD_SIN_TABLAS', 'message' => 'A la base de datos le faltan tablas o columnas. Importa database/sigep_schema_sin_datos.sql (instalación nueva) o database/migracion_hu14.sql (si ya tenías sigep_db).']], 503);
    }
    responder(['error' => ['code' => 'ERROR_INTERNO', 'message' => 'Ocurrió un error. Inténtalo de nuevo.']], 500);
});

sigep_iniciar_sesion();

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        error_log('[sigep] No se pudo conectar a la BD: ' . $e->getMessage());
        fallar(503, 'BD_NO_DISPONIBLE', 'No se pudo conectar a la base de datos. Verifica que MySQL esté iniciado en XAMPP y que importaste database/sigep_schema_sin_datos.sql (ver README).');
    }
    return $pdo;
}

function solo_metodo(string $metodo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $metodo) {
        header('Allow: ' . $metodo);
        fallar(405, 'METODO_NO_PERMITIDO', 'Método no permitido.');
    }
}

/** Protección extra contra envíos desde otros sitios (además de SameSite=Lax). */
function verificar_origen(): void
{
    $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origen === '') {
        return;
    }
    $p = parse_url($origen);
    $host = ($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : '');
    if (strcasecmp($host, $_SERVER['HTTP_HOST'] ?? '') !== 0) {
        fallar(403, 'ORIGEN_NO_PERMITIDO', 'Solicitud no permitida.');
    }
}

function leer_json(): array
{
    $crudo = file_get_contents('php://input');
    if ($crudo === false || $crudo === '') {
        return [];
    }
    $d = json_decode($crudo, true);
    if (!is_array($d)) {
        fallar(400, 'JSON_INVALIDO', 'Solicitud no válida.');
    }
    return $d;
}

const PENDIENTE_VIGENCIA = 1800; // el registro pendiente caduca a los 30 min

function pendiente_actual(): ?array
{
    $p = $_SESSION['pending'] ?? null;
    if (!is_array($p) || (time() - (int) ($p['at'] ?? 0)) > PENDIENTE_VIGENCIA) {
        return null;
    }
    return $p;
}

function usuario_actual_id(): ?int
{
    return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
}
