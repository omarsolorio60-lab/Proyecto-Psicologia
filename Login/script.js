const form = document.getElementById("loginForm");
const inputCorreo = document.getElementById("correo");
const inputContrasena = document.getElementById("contrasena");
const mensajeError = document.getElementById("mensaje");

function mostrarMensajeError(mensaje) {
  mensajeError.textContent = mensaje;
}

function limpiarMensajeError() {
  mensajeError.textContent = "";
}

form.addEventListener("submit", function (event) {
  event.preventDefault();
  limpiarMensajeError();

  const correo = inputCorreo.value.trim().toLowerCase();
  const contrasena = inputContrasena.value;

  if (!correo || !contrasena) {
    mostrarMensajeError("Por favor, complete todos los campos.");
    return;
  }

  const REGEX_CORREO = /^[a-zA-Z0-9._%+-]+@uabc\.edu\.mx$/;
  if (!REGEX_CORREO.test(correo)) {
    mostrarMensajeError("Por favor, ingrese un correo electrónico válido de UABC.");
    return;
  }

  const usuario = usuarios.find(function (u) {
    return u.correo === correo && u.contrasena === contrasena;
  });

  if (!usuario) {
    mostrarMensajeError("Correo electrónico o contraseña incorrectos.");
    inputContrasena.value = "";
    return;
  }

  sessionStorage.setItem("sesion", JSON.stringify({
    correo: usuario.correo,
    rol: usuario.rol
  }));

  window.location.href = rutas_por_rol[usuario.rol];
});