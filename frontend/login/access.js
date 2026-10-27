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
