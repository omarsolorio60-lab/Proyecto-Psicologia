# SIGEP - Sistema Psicologia
Sistema de gestion de pacientes de psicologia para UABC

**Equipo de desarrollo**
- Omar duran
- Miguel Tarin
- America Martinez
- Dariana Agundez
- Angel Reyes

Stack: **PHP 7.4+ · MySQL/MariaDB · HTML/CSS/JS sin frameworks**. Pensado para **XAMPP**.

---

## 1. Puesta en marcha en XAMPP

1. **Copia la carpeta** del proyecto dentro de `htdocs` (por ejemplo `C:\xampp\htdocs\sigep`).
2. **Enciende Apache y MySQL** en el *XAMPP Control Panel*.
3. **Base de datos** (http://localhost/phpmyadmin → pestaña **Importar**):
   - **Instalación nueva:** importa `database/sigep_schema_sin_datos.sql` (crea la base `sigep_db` por sí solo) y después `database/datos_prueba.sql`.
   - **¿Ya tenías `sigep_db` de la versión anterior?** NO borres nada: importa **una sola vez** `database/migracion_hu14.sql`. Agrega lo nuevo y conserva tus datos.
4. **Revisa** http://localhost/sigep/diagnostico.php → debe decir **✔ Todo listo** (los avisos amarillos son normales en desarrollo).
5. **Usa el sistema:** http://localhost/sigep/

> Si tu MySQL no es `root` sin contraseña, edita `DB_USER` / `DB_PASS` en **`config/config.php`** (es el único archivo de configuración de todo el sistema).

### Prueba rápida (con `datos_prueba.sql` importado)
En el acceso elige **"Soy estudiante: crear mi cuenta o entrar con Google"** y usa el recuadro amarillo **"Entrar sin Google (pruebas)"**:

| Correo | Matrícula | Resultado esperado |
|---|---|---|
| `alguien@gmail.com` | — | Rechazado: solo @uabc.edu.mx |
| `juan.perez@uabc.edu.mx` | `1234567` | Se **vincula** al alumno que ya existía (mismo correo → sin revisión) |
| `otra.persona@uabc.edu.mx` | `1234567` (después de lo anterior) | Error: la matrícula ya tiene cuenta |
| `maria.s@uabc.edu.mx` | `2345678` | Se vincula, pero queda **por revisar** (la psicóloga lo registró sin correo) |
| `nuevo.alumno@uabc.edu.mx` | `7654321` | Se crea un alumno nuevo |

Después pulsa **"Solicitar cita"**: la página de citas ya reconoce la sesión y no vuelve a pedir los datos.

---

## 2. Módulos

| Módulo | Dónde | Estado |
|---|---|---|
| Acceso (selector) | `frontend/login/access.php` | Funciona |
| **Registro de cuenta del estudiante con Google (HU-14)** | `frontend/registro/` + `backend/cuenta/` | Funciona |
| Solicitud de citas | `frontend/citas/` + `backend/save_appointment.php`, `availability.php` | Funciona, con o sin cuenta |
| Panel de estudiantes | `frontend/estudiantes/` | Interfaz con datos de prueba (`mock-estudiantes.js`) |
| Aspirantes | `frontend/aspirantes/` | Guarda en el navegador (pendiente conectar a `applicants`) |
| Login de administrador | `backend/admin_login.php` | Pendiente (la tabla `users` ya tiene `role` y `password_hash`) |

## 3. Estructura
```
index.php                  redirige al acceso
config/                    [bloqueada por .htaccess]
  config.php               ← ÚNICO archivo que editas (BD, Google, DEV_MODE, reglas, zona horaria, sesión)
  database_connection.php  conexión para citas/disponibilidad
  api_bootstrap.php · cuenta_helpers.php · alumno_sesion.php
backend/
  save_appointment.php · availability.php · admin_login.php
  cuenta/                  opciones · me · auth_google · registro · horario · logout   (HU-14)
frontend/
  login/ · citas/ · registro/ (HU-14) · estudiantes/ · aspirantes/
database/                  [bloqueada por .htaccess]
  sigep_schema_sin_datos.sql · migracion_hu14.sql · datos_prueba.sql
storage/horarios/          horarios subidos por los alumnos   [bloqueada por .htaccess]
diagnostico.php            revisa que todo esté bien (borrar al publicar)
tests/                     pruebas automáticas (opcional)
```

## 4. Reglas compartidas (para que todos los formularios sean compatibles)
Están en `config/config.php` y las usan el registro, las citas y el panel:
- **Una sola tabla de alumnos: `students`.** Su columna `registration_source` dice quién lo creó: `Cita`, `Autoregistro` o `Psicologa`.
- **Matrícula:** `MATRICULA_DIGITOS` = 7 dígitos. **Semestre:** 1 a `SEMESTRE_MAX` = 12.
- **Sexo:** `M` = Mujer, `H` = Hombre, `ND` = Prefiero no decirlo (igual en BD, registro y panel).
- **Carreras:** tabla `programs` (confirmar la lista oficial con las clientas).
- **Una sola sesión** (`sigep_sess`) para todo el sistema. Zona horaria `America/Tijuana`.

### Cómo se cruzan citas y cuentas
- Alumno **con sesión** → la cita se registra a su nombre; los datos del formulario se ignoran.
- Alumno **sin sesión** → igual que antes (matrícula + correo). Si esa matrícula **ya tiene cuenta**, se le pide iniciar sesión.
- Alumno que pidió cita sin cuenta y **después** crea su cuenta con el mismo correo → se vincula a su registro, sin duplicar.
- Si la matrícula existía con **otro correo o sin correo** → se vincula pero queda `link_needs_review = 1` para que la psicóloga confirme (al alumno se le muestra solo el nombre que él escribió).

---

## 5. Inicio de sesión real con Google
1. https://console.cloud.google.com → crea un proyecto.
2. **APIs y servicios → Pantalla de consentimiento de OAuth**: **Interno** si la cuenta es de UABC; si no, **Externo** en modo pruebas con correos de prueba.
3. **Credenciales → Crear credenciales → ID de cliente de OAuth → Aplicación web**.
4. **Orígenes de JavaScript autorizados**: `http://localhost` (y `http://localhost:8080` si usas ese puerto).
5. Pega el **ID de cliente** en `config/config.php` → `GOOGLE_CLIENT_ID`.
6. Recarga `diagnostico.php`.

El servidor vuelve a validar el token con Google (auténtico, emitido para tu aplicación, vigente, correo verificado) y exige el dominio exacto `@uabc.edu.mx`.

### Antes de publicar
- `DEV_MODE` → **`false`** en `config/config.php`.
- Borra `diagnostico.php` y la carpeta `tests/`.
- Usa **HTTPS** y un usuario de MySQL propio con contraseña.

## 6. Pruebas automáticas (opcional)
Con MySQL encendido, en una terminal dentro de la carpeta del proyecto:
```bash
php tests/probar.php
```
(En Windows: `C:\xampp\php\php.exe tests\probar.php`.) Usa una base **aparte** (`sigep_test`, se borra al terminar) y una copia temporal: **no toca tus datos**. Debe terminar con `0 fallaron`.

## 7. Errores comunes

| Síntoma | Solución |
|---|---|
| "No se pudo conectar a la base de datos" | Start en MySQL; revisa `config/config.php`; importa el esquema |
| "A la base de datos le faltan tablas o columnas" | Importa `database/migracion_hu14.sql` (si ya tenías `sigep_db`) |
| La página se queda en "Cargando…" | Abre siempre `http://localhost/...`, nunca con doble clic (`file://`) |
| Acentos rotos (`IngenierÃ­a`) | Borra la base e impórtala de nuevo (los `.sql` incluyen `SET NAMES utf8mb4`) |
| "Error de certificado SSL" con Google | Descarga https://curl.se/ca/cacert.pem y pon su ruta en `CA_BUNDLE` |
| Google: `origin_mismatch` | Agrega exactamente `http://localhost` (con puerto si aplica) en la consola de Google |
| "El archivo supera 5 MB" o falla la subida | En `php.ini` pon `upload_max_filesize=8M` y `post_max_size=8M`, reinicia Apache |
| "No se pudo guardar el archivo" | Da permisos de escritura a `storage/horarios` |
| Falta una extensión (`curl`, `fileinfo`, `mbstring`, `pdo_mysql`) | Quita el `;` de `extension=...` en `php.ini` y reinicia Apache |
| No aparecen los errores en pantalla | Es intencional: revisa `C:\xampp\apache\logs\error.log` (busca `[sigep]`) |
