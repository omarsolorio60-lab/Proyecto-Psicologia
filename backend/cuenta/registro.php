<?php
/**
 * HU-14 · Criterios 2, 3 y 4: crear el perfil del alumno (o vincular la cuenta a uno
 * existente en la tabla students) y guardar el horario.
 * Recibe multipart/form-data: matricula, nombre, apellido_paterno, [apellido_materno],
 * sexo, telefono, programa_id, semestre, grupo, [horario].
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/api_bootstrap.php';

solo_metodo('POST');
verificar_origen();

$pendiente = pendiente_actual();
if (!$pendiente) {
    fallar(401, 'SIN_SESION', 'Tu sesión expiró. Inicia sesión con Google otra vez.');
}

$maxMB = HORARIO_MAX_BYTES / 1024 / 1024;
$msgGrande = "El archivo supera $maxMB MB. Elige uno más ligero.";

// Si el envío excede post_max_size, PHP descarta TODO el cuerpo (quedan $_POST y $_FILES vacíos).
if (!$_POST && !$_FILES && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    fallar(413, 'ARCHIVO_GRANDE', $msgGrande, ['horario' => $msgGrande]);
}

$pdo = db();
$ids = array_map('intval', $pdo->query('SELECT program_id FROM programs WHERE active = 1')->fetchAll(PDO::FETCH_COLUMN));
[$errores, $v] = validar_registro($_POST, $ids);

// ---- Criterio 4: archivo opcional (JPG/PNG/PDF, máx. 5 MB, tipo real por contenido) ----
$tipo = null;
$estadoArchivo = 422;
$f = $_FILES['horario'] ?? null;
if (is_array($f) && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || (int) $f['size'] > HORARIO_MAX_BYTES) {
        $errores['horario'] = $msgGrande;
        $estadoArchivo = 413;
    } elseif ($f['error'] !== UPLOAD_ERR_OK) {
        $errores['horario'] = 'No se pudo subir el archivo. Inténtalo de nuevo.';
    } elseif ((int) $f['size'] === 0) {
        $errores['horario'] = 'El archivo está vacío.';
    } else {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset(TIPOS_HORARIO[$mime])) {
            $errores['horario'] = 'El horario debe ser JPG, PNG o PDF.';
        } else {
            $tipo = $mime;
        }
    }
}
if ($errores) {
    $soloArchivo = count($errores) === 1 && isset($errores['horario']);
    fallar($soloArchivo ? $estadoArchivo : 422, $soloArchivo ? ($estadoArchivo === 413 ? 'ARCHIVO_GRANDE' : 'ARCHIVO_INVALIDO') : 'VALIDACION', 'Revisa los campos marcados.', $errores);
}

// ---- Guardar archivo (fuera del alcance de las URLs; storage/ está bloqueada por .htaccess) ----
$nombreArchivo = null;
$dir = HORARIOS_DIR;
if ($tipo !== null) {
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        fallar(500, 'SIN_ALMACENAMIENTO', 'No se pudo crear la carpeta storage/horarios.');
    }
    $nombreArchivo = bin2hex(random_bytes(16)) . '.' . TIPOS_HORARIO[$tipo];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $nombreArchivo)) {
        fallar(500, 'SIN_ALMACENAMIENTO', 'No se pudo guardar el archivo. Revisa los permisos de storage/horarios.');
    }
}

// ---- Criterio 3: crear o vincular, todo en una transacción ----
$email = $pendiente['email'];
$vinculado = false;
$archivoAnterior = null;
$usuarioId = 0;
try {
    $pdo->beginTransaction();

    $st = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
    $st->execute([$email]);
    if ($st->fetch()) {
        fallar(409, 'EMAIL_YA_REGISTRADO', 'Esta cuenta de Google ya está registrada. Inicia sesión.');
    }

    $st = $pdo->prepare("INSERT INTO users (email, google_sub, role) VALUES (?, ?, 'estudiante')");
    $st->execute([$email, $pendiente['sub'] !== '' ? $pendiente['sub'] : null]);
    $usuarioId = (int) $pdo->lastInsertId();

    $st = $pdo->prepare('SELECT student_id, user_id, institutional_email, schedule_file FROM students WHERE student_number = ? FOR UPDATE');
    $st->execute([$v['matricula']]);
    $existente = $st->fetch();

    // ¿Este correo ya pertenece a OTRO alumno (por ejemplo, pidió cita con otra matrícula)?
    $st = $pdo->prepare('SELECT student_id FROM students WHERE institutional_email = ? AND student_number <> ? LIMIT 1 FOR UPDATE');
    $st->execute([$email, $v['matricula']]);
    if ($st->fetch()) {
        fallar(409, 'CORREO_EN_OTRA_MATRICULA', 'Tu correo ya está registrado con otra matrícula (por ejemplo, al pedir una cita). Revisa tu matrícula o acude al área psicopedagógica.', ['matricula' => 'Esta matrícula no coincide con la que tenemos para tu correo.']);
    }

    if ($existente) {
        if ($existente['user_id'] !== null) {
            fallar(409, 'MATRICULA_VINCULADA', 'Esta matrícula ya tiene una cuenta. Si no fuiste tú, avisa al área psicopedagógica.');
        }
        // El alumno ya existía (lo registró la psicóloga o pidió cita antes): se VINCULA, no se duplica.
        // Si el correo guardado es el mismo que verificó Google, es la misma persona.
        // Si no coincide (o no había correo), la psicóloga debe confirmar la identidad.
        $guardado = $existente['institutional_email'];
        $mismoCorreo = $guardado !== null && strcasecmp(trim((string) $guardado), $email) === 0;
        $archivoAnterior = $nombreArchivo !== null ? $existente['schedule_file'] : null;
        $st = $pdo->prepare(
            'UPDATE students
                SET user_id = ?, phone = ?, program_id = ?, semester = ?, group_code = ?,
                    sex = COALESCE(sex, ?),
                    institutional_email = COALESCE(institutional_email, ?),
                    declared_name = ?, link_needs_review = ?,
                    schedule_file = COALESCE(?, schedule_file),
                    schedule_mime = COALESCE(?, schedule_mime)
              WHERE student_id = ?'
        );
        $st->execute([
            $usuarioId, $v['telefono'], $v['programa_id'], $v['semestre'], $v['grupo'],
            $v['sexo'], $email, $v['nombre_completo'], $mismoCorreo ? 0 : 1,
            $nombreArchivo, $tipo, $existente['student_id'],
        ]);
        $vinculado = true;
    } else {
        $st = $pdo->prepare(
            "INSERT INTO students
                (user_id, student_number, first_name, paternal_last_name, maternal_last_name,
                 institutional_email, phone, sex, program_id, semester, group_code,
                 schedule_file, schedule_mime, registration_source)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Autoregistro')"
        );
        $st->execute([
            $usuarioId, $v['matricula'], $v['nombre'], $v['apellido_paterno'],
            $v['apellido_materno'] !== '' ? $v['apellido_materno'] : null,
            $email, $v['telefono'], $v['sexo'], $v['programa_id'], $v['semestre'], $v['grupo'],
            $nombreArchivo, $tipo,
        ]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($nombreArchivo !== null) {
        @unlink($dir . '/' . $nombreArchivo); // no dejar archivos huérfanos
    }
    if ($e instanceof PDOException && (string) $e->getCode() === '23000') {
        fallar(409, 'DUPLICADO', 'Esa matrícula o cuenta ya está registrada.');
    }
    throw $e;
}

if ($archivoAnterior) {
    @unlink($dir . '/' . basename((string) $archivoAnterior));
}
session_regenerate_id(true);
unset($_SESSION['pending']);
$_SESSION['usuario_id'] = $usuarioId;

responder(['estado' => 'registrado', 'vinculado' => $vinculado, 'estudiante' => vista_estudiante($pdo, $usuarioId)], 201);
