<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso | Sistema Psicopedagógico UABC</title>
  <link rel="stylesheet" href="access.css">
  <script src="access.js" defer></script>
</head>

<body>
  <main class="layout">
    <section class="presentacion" aria-label="Información del sistema">
      <header class="universidad">
        <div class="">UABC</div>
        <div><strong>Universidad Autónoma de Baja California</strong><span>Facultad de Ciencias de la Ingeniería y
            Tecnología</span></div>
      </header>
      <div class="descripcion">
        <h1>Sistema de<br>Seguimiento<br>Psicopedagógico</h1>
      </div>
    </section>
    <section class="acceso" aria-label="Inicio de sesión">
      <div class="contenido-acceso">
        <div class="tarjeta">
          <div id="selector">

            <h2>Bienvenido a SIGEP</h2>

            <p class="subtitulo">Selecciona cómo deseas ingresar al sistema.</p>
            <a class="access-choice" href="../registro/index.html">
              <strong>Soy estudiante: crear mi cuenta o entrar con Google</strong>
            </a>

            <a class="access-choice" href="../citas/index.php">
              <strong>Soy estudiante y quiero una cita</strong>
            </a>

            <a class="access-choice" href="../aspirantes/aspirantes.html">
              <strong>Soy aspirante</strong>
            </a>

            <button class="access-choice admin-choice" type="button" id="showAdmin"><strong>
              Soy administrador</strong></button>
          </div>
          <div id="adminPanel" hidden>
            <h2>Acceso de administrador</h2>
            <p class="subtitulo">Ingresa con tu cuenta institucional.</p>

            <form id="loginForm" action="../../backend/admin_login.php" method="post">
              <label for="correo">Correo institucional</label>
              <input id="correo" name="correo" type="email" placeholder="nombre.apellido@uabc.edu.mx"
                autocomplete="username" required>
              <label for="contrasena">Contraseña</label>
              <input id="contrasena" name="contrasena" type="password" placeholder="Ingresa tu contraseña"
                autocomplete="current-password" required>
              <div class="opciones">
                <label class="recordar"><input type="checkbox" id="recordar"> Recordarme</label>

              </div>
              <button type="submit">Entrar</button>
              <p id="mensaje" class="mensaje" role="status" aria-live="polite"></p>
            </form><button type="button" id="backSelector" class="back-choice">← Volver a las opciones</button>
          </div>
        </div>
      </div>
    </section>
  </main>
</body>

</html>