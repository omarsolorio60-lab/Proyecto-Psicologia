const selector = document.getElementById("selector");
const adminPanel = document.getElementById("adminPanel");
document.getElementById("showAdmin").addEventListener("click", () => {
  selector.hidden = true;
  adminPanel.hidden = false;
});
document.getElementById("backSelector").addEventListener("click", () => {
  adminPanel.hidden = true;
  selector.hidden = false;
});
document.getElementById("loginForm").addEventListener("submit", (e) => {
  const email = document.getElementById("correo").value;
  const pass = document.getElementById("contrasena").value;
  const msg = document.getElementById("mensaje");
  msg.textContent = "";
  if (
    email !== email.trim() ||
    !/^[^\s@]+@uabc\.edu\.mx$/i.test(email) ||
    !pass.trim()
  ) {
    e.preventDefault();
    msg.textContent =
      "Completa los campos e ingresa un correo @uabc.edu.mx válido.";
  }
});


// Mensaje de error cuando admin_login.php regresa con ?error=...
const LOGIN_ERRORS = {
  datos: "Completa los campos e ingresa un correo @uabc.edu.mx v\u00e1lido.",
  credenciales: "Correo o contrase\u00f1a incorrectos.",
  servidor: "No se pudo conectar con el servidor. Intenta de nuevo."
};
const loginError = new URLSearchParams(window.location.search).get("error");
if (loginError && LOGIN_ERRORS[loginError]) {
  selector.hidden = true;
  adminPanel.hidden = false;
  document.getElementById("mensaje").textContent = LOGIN_ERRORS[loginError];
}