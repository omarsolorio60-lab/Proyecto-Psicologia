<?php
/**
 * Alumno con sesión iniciada (cuenta de Google, HU-14).
 * Lo usan las citas: si el alumno ya inició sesión, la cita se registra a su nombre
 * sin volver a pedirle sus datos.
 */
require_once __DIR__ . '/config.php';

/** Devuelve el registro de students de la cuenta en sesión, o null si no hay sesión de alumno. */
function sigep_alumno_en_sesion(PDO $pdo): ?array
{
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }
    $st = $pdo->prepare(
        "SELECT s.student_id, s.student_number, s.institutional_email, u.email AS account_email,
                CASE WHEN s.link_needs_review = 1 AND s.declared_name IS NOT NULL THEN s.declared_name
                     ELSE CONCAT_WS(' ', s.first_name, s.paternal_last_name, s.maternal_last_name) END AS visible_name
           FROM students s
           JOIN users u ON u.user_id = s.user_id
          WHERE s.user_id = ? AND u.active = 1 AND u.role = 'estudiante'"
    );
    $st->execute([(int) $_SESSION['usuario_id']]);
    $fila = $st->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
}

/** Igual que la anterior, pero para páginas HTML: si la BD falla, se comporta como "sin sesión". */
function sigep_alumno_en_sesion_html(): ?array
{
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }
    try {
        $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME), DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return sigep_alumno_en_sesion($pdo);
    } catch (Throwable $e) {
        error_log('SIGEP sesión alumno: ' . $e->getMessage());
        return null;
    }
}
