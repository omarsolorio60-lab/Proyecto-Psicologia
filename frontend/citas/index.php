<?php
session_start();
if (empty($_SESSION['csrf_citas'])) {
    $_SESSION['csrf_citas'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIGEP | Solicitar cita</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../shared/validation.css">
</head>

<body>
    <header class="top-navbar">
        <button class="hamburger" type="button" onclick="ToggleEvent()" aria-label="Mostrar u ocultar menú">☰</button>
        <div class="top-brand">
            <h2>Psicopedagógico</h2>
            <small>FCITEC - UABC</small>
        </div>
    </header>

    <div class="container">
        <nav class="sidebar" id="sidebar" aria-label="Navegación principal">
            <a href="../login/access.php" class="menu-item">← Inicio / Acceso</a>
            <a href="index.php" class="menu-item active">Registro de citas</a>
            <a href="../aspirantes/aspirantes.html" class="menu-item">Registro Aspirantes</a>
        </nav>

        <div class="content">
    <main class="app">
        <header>
            <div class="brand">SIGEP <span>· Atención psicopedagógica</span></div>
            <div class="progress">
                <div id="bar"></div>
            </div>
        </header>
        <input type="hidden" id="csrf" value="<?= htmlspecialchars($_SESSION['csrf_citas'], ENT_QUOTES, 'UTF-8') ?>">
        <section id="accountGate" class="account-gate">
            <h1>Bienvenido a SIGEP</h1>
            <p class="sub">Para solicitar una cita, crea tu cuenta o verifica tu registro.</p><button type="button"
                class="primary" id="createAccount">Crear cuenta</button><button type="button"
                class="outline account-login" id="existingAccount">Ya tengo una cuenta</button><a href="../login/access.php" class="outline account-login back-link">← Regresar</a>
        </section>
        <dialog id="loginDialog" aria-labelledby="loginTitle">
            <form id="loginStudentForm" novalidate>
                <h2 id="loginTitle">Verificar mi cuenta</h2>
                <p class="sub">Ingresa tu matrícula y correo institucional.</p><label
                    for="loginMatricula">Matrícula</label><input id="loginMatricula" required placeholder="Ej. 1270021"><label
                    for="loginEmail">Correo UABC</label><input id="loginEmail" type="email" required
                    placeholder="nombre@uabc.edu.mx">
                <p id="loginError" role="alert"></p><button type="submit" class="primary">Continuar a mi
                    cita</button><button type="button" class="outline account-login" id="closeDialog">Regresar</button>
            </form>
        </dialog>
        <section class="step" data-step="1">
            <p class="eyebrow">Paso 1 de 4</p>
            <h1>Crea tu cuenta</h1>
            <p class="sub">Ingresa tus propios datos para solicitar atención. No mostramos información de otros
                estudiantes.</p>
            <form id="alumnoForm" novalidate>
                <label for="matricula">Matrícula</label><input id="matricula" name="matricula" required
                    autocomplete="off" placeholder="Ej. 1270021">
                <label for="nombre">Nombre(s)</label><input id="nombre" name="nombre" maxlength="100" required
                    autocomplete="given-name" placeholder="Tu nombre">
                <div class="two">
                    <div><label for="apellido_paterno">Apellido paterno</label><input id="apellido_paterno"
                            name="apellido_paterno" maxlength="100" required autocomplete="family-name"></div>
                    <div><label for="apellido_materno">Apellido materno</label><input id="apellido_materno"
                            name="apellido_materno" maxlength="100" required autocomplete="additional-name"></div>
                </div>
                <label for="correo_institucional">Correo institucional UABC</label><input id="correo_institucional"
                    name="correo_institucional" type="email" maxlength="150" required placeholder="nombre@uabc.edu.mx"
                    autocomplete="email">
                <label for="telefono">Teléfono (10 dígitos)</label><input id="telefono" name="telefono" type="tel"
                    required autocomplete="tel" placeholder="Ej. 6641234567">
                <label for="carrera">Carrera</label><select id="carrera" name="carrera" required><option value="">Cargando carreras...</option></select>
                <label for="semestre">Semestre</label><input id="semestre" name="semestre" type="number"
                    min="1" max="20" required>
                <p class="hint">Todos los campos son obligatorios. Tus datos se guardarán una sola vez. Si ya tienes cuenta, vuelve y selecciona «Ya tengo
                    una cuenta».</p>
                <p id="accountError" class="field-error" role="alert"></p>
                <p class="hint" id="lockedNote" hidden>Tu cuenta ya fue creada, por eso estos datos ya no se pueden modificar.</p>
                <div class="two bottom"><button class="outline back" type="button">Regresar</button><button class="primary next" type="button">Crear cuenta y continuar</button></div>
            </form>
        </section>
        <section class="step" data-step="2">
            <p class="eyebrow">Paso 2 de 4</p>
            <h1>Motivo de consulta</h1>
            <p class="sub">Cuéntanos en qué podemos apoyarte.</p><label>¿Cuál es el motivo de tu consulta?</label>
            <div class="choices" id="motivos"><button type="button" class="choice selected"
                    data-value="Académico"><b>Académico</b><small>Bajo rendimiento, estrés por
                        materias</small></button><button type="button" class="choice"
                    data-value="Personal o emocional"><b>Personal o emocional</b><small>Situación familiar, estado de
                        ánimo</small></button><button type="button" class="choice"
                    data-value="Orientación vocacional"><b>Orientación vocacional</b><small>Dudas sobre tu
                        carrera</small></button><button type="button" class="choice"
                    data-value="Justificante"><b>Justificante</b><small>Solicitar un justificante
                        académico</small></button><button type="button" class="choice"
                    data-value="Otro"><b>Otro</b><small>Describe brevemente tu situación</small></button></div><label
                for="detalle" id="detalleLabel">Cuéntanos un poco más (opcional)</label><textarea id="detalle" maxlength="180"
                placeholder="Describe brevemente el motivo de tu consulta"></textarea><label>Modalidad</label>
            <div class="two"><button type="button" class="mode selected"
                    data-value="Presencial">Presencial</button><button type="button" class="mode"
                    data-value="Virtual">En línea</button></div>
            <div class="two bottom"><button class="outline back" type="button">Regresar</button><button
                    class="primary next" type="button">Continuar</button></div>
        </section>
        <section class="step" data-step="3">
            <p class="eyebrow">Paso 3 de 4</p>
            <h1>Elige tu horario</h1>
            <p class="sub">Selecciona la fecha y hora que te gustaría solicitar.</p><label for="fecha">Fecha
                deseada</label><input type="date" id="fecha" required><label>Horario deseado</label>
            <div class="hours" id="hours"></div>
            <p class="hint">Este horario es una propuesta. La psicóloga confirmará la fecha definitiva posteriormente.
            </p>
            <div class="two bottom"><button class="outline back" type="button">Regresar</button><button
                    class="primary next" type="button">Revisar solicitud</button></div>
        </section>
        <section class="step" data-step="4">
            <p class="eyebrow">Paso 4 de 4</p>
            <h1>Revisa tu solicitud</h1>
            <p class="sub">Verifica tus datos antes de registrarlos.</p>
            <div class="summary" id="summary"></div>
            <p class="hint">Tu solicitud quedará pendiente de confirmación.</p>
            <div id="error" role="alert" aria-live="polite"></div>
            <div class="two bottom"><button class="outline back" type="button">Regresar</button><button class="primary"
                    id="submit" type="button">Registrar cita</button></div>
        </section>
        <section class="step done" data-step="5">
            <div class="check">✓</div>
            <h1>¡Tu solicitud quedó registrada!</h1>
            <p class="sub">La psicóloga revisará tu solicitud y confirmará el horario.</p>
            <div class="summary" id="result"></div><button class="primary" type="button"
                onclick="location.reload()">Nueva solicitud</button>
        </section>
    </main>
        </div>
    </div>
    <script src="../shared/validation.js"></script>
    <script src="script.js?v=2"></script>
</body>

</html>