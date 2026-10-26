<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/alumno_sesion.php';
sigep_iniciar_sesion();
if (empty($_SESSION['csrf_citas'])) { $_SESSION['csrf_citas'] = bin2hex(random_bytes(32)); }
// Si el alumno ya inició sesión con Google (frontend/registro), no se le vuelven a pedir sus datos.
$alumno = sigep_alumno_en_sesion_html();
$e = static function ($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>
<!doctype html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>SIGEP | Solicitar cita</title><link rel="stylesheet" href="style.css"></head>
<body><main class="app"<?php if ($alumno): ?> data-sesion="1" data-nombre="<?= $e($alumno['visible_name']) ?>" data-matricula="<?= $e($alumno['student_number']) ?>" data-correo="<?= $e($alumno['institutional_email'] ?? $alumno['account_email']) ?>"<?php endif; ?>>
<header><div class="brand">SIGEP <span>· Atención psicopedagógica</span></div><div class="progress"><div id="bar"></div></div></header>
<input type="hidden" id="csrf" value="<?= htmlspecialchars($_SESSION['csrf_citas'], ENT_QUOTES, 'UTF-8') ?>">
<section class="step active" data-step="1"><p class="eyebrow">Paso 1 de 4</p>
<?php if ($alumno): ?>
<h1>Tus datos</h1><p class="sub">Iniciaste sesión con tu cuenta institucional. La cita se registrará a tu nombre.</p>
<div class="summary"><div><b>Alumno: </b><?= $e($alumno['visible_name']) ?></div><div><b>Matrícula: </b><?= $e($alumno['student_number']) ?></div><div><b>Correo: </b><?= $e($alumno['institutional_email'] ?? $alumno['account_email']) ?></div></div>
<p class="hint">¿No eres tú? <a href="../registro/index.html">Cambia de cuenta</a>.</p>
<button class="primary next" type="button">Continuar</button></section>
<?php else: ?>
<h1>Registra tus datos</h1><p class="sub">Ingresa tus propios datos para solicitar atención. No mostramos información de otros estudiantes.</p>
<form id="alumnoForm" novalidate>
<label for="matricula">Matrícula</label><input id="matricula" name="matricula" maxlength="<?= MATRICULA_DIGITOS ?>" pattern="\d{<?= MATRICULA_DIGITOS ?>}" title="<?= MATRICULA_DIGITOS ?> dígitos" inputmode="numeric" required autocomplete="off" placeholder="Ej. 1270021">
<label for="nombre">Nombre(s)</label><input id="nombre" name="nombre" maxlength="100" required autocomplete="given-name" placeholder="Tu nombre">
<div class="two"><div><label for="apellido_paterno">Apellido paterno</label><input id="apellido_paterno" name="apellido_paterno" maxlength="100" required autocomplete="family-name"></div><div><label for="apellido_materno">Apellido materno (opcional)</label><input id="apellido_materno" name="apellido_materno" maxlength="100"></div></div>
<label for="correo_institucional">Correo institucional UABC</label><input id="correo_institucional" name="correo_institucional" type="email" maxlength="150" required placeholder="nombre@uabc.edu.mx" autocomplete="email">
<label for="telefono">Teléfono (opcional)</label><input id="telefono" name="telefono" type="tel" maxlength="20" autocomplete="tel">
<label for="carrera">Carrera (opcional)</label><input id="carrera" name="carrera" maxlength="120">
<label for="semestre">Semestre (opcional)</label><input id="semestre" name="semestre" type="number" min="1" max="<?= SEMESTRE_MAX ?>">
<p class="hint">Si ya te registraste anteriormente, utilizaremos tu matrícula y correo para asociar la nueva solicitud a tu registro existente. No ingreses información de otra persona.</p>
<p class="hint">¿Tienes cuenta? <a href="../registro/index.html">Inicia sesión con Google</a> y no tendrás que escribir tus datos.</p>
<button class="primary next" type="button">Continuar</button></form></section>
<?php endif; ?>
<section class="step" data-step="2"><p class="eyebrow">Paso 2 de 4</p><h1>Motivo de consulta</h1><p class="sub">Cuéntanos en qué podemos apoyarte.</p><label>¿Cuál es el motivo de tu consulta?</label><div class="choices" id="motivos"><button type="button" class="choice selected" data-value="Académico"><b>Académico</b><small>Bajo rendimiento, estrés por materias</small></button><button type="button" class="choice" data-value="Personal o emocional"><b>Personal o emocional</b><small>Situación familiar, estado de ánimo</small></button><button type="button" class="choice" data-value="Orientación vocacional"><b>Orientación vocacional</b><small>Dudas sobre tu carrera</small></button><button type="button" class="choice" data-value="Justificante"><b>Justificante</b><small>Solicitar un justificante académico</small></button><button type="button" class="choice" data-value="Otro"><b>Otro</b><small>Describe brevemente tu situación</small></button></div><label for="detalle">Cuéntanos un poco más (opcional)</label><textarea id="detalle" maxlength="180" placeholder="Describe brevemente el motivo de tu consulta"></textarea><label>Modalidad</label><div class="two"><button type="button" class="mode selected" data-value="Presencial">Presencial</button><button type="button" class="mode" data-value="Virtual">En línea</button></div><div class="two bottom"><button class="outline back" type="button">Regresar</button><button class="primary next" type="button">Continuar</button></div></section>
<section class="step" data-step="3"><p class="eyebrow">Paso 3 de 4</p><h1>Elige tu horario</h1><p class="sub">Selecciona la fecha y hora que te gustaría solicitar.</p><label for="fecha">Fecha deseada</label><input type="date" id="fecha" required><label>Horario deseado</label><div class="hours" id="hours"></div><p class="hint">Este horario es una propuesta. La psicóloga confirmará la fecha definitiva posteriormente.</p><div class="two bottom"><button class="outline back" type="button">Regresar</button><button class="primary next" type="button">Revisar solicitud</button></div></section>
<section class="step" data-step="4"><p class="eyebrow">Paso 4 de 4</p><h1>Revisa tu solicitud</h1><p class="sub">Verifica tus datos antes de registrarlos.</p><div class="summary" id="summary"></div><p class="hint">Tu solicitud quedará pendiente de confirmación.</p><div id="error" role="alert" aria-live="polite"></div><div class="two bottom"><button class="outline back" type="button">Regresar</button><button class="primary" id="submit" type="button">Registrar cita</button></div></section>
<section class="step done" data-step="5"><div class="check">✓</div><h1>¡Tu solicitud quedó registrada!</h1><p class="sub">La psicóloga revisará tu solicitud y confirmará el horario.</p><div class="summary" id="result"></div><button class="primary" type="button" onclick="location.reload()">Nueva solicitud</button></section>
</main><script src="script.js"></script></body></html>
