// Funciones para hablar con el backend en PHP.
// Las usan Inicio y Estudiantes. La ruta es relativa a frontend/<pagina>/.
const API_BASE = "../../backend/";

const LOGIN_PAGE = "../login/access.php";

async function readResponse(response) {
  // Sin sesion de psicologa: regresar al login
  if (response.status === 401) {
    window.location.href = LOGIN_PAGE;
    throw new Error("Inicia sesion para continuar.");
  }

  let data = null;
  try {
    data = await response.json();
  } catch (error) {
    // El PHP respondio algo que no es JSON (por ejemplo un error de PHP)
    throw new Error("Respuesta invalida del servidor (" + response.status + ").");
  }
  if (!response.ok || !data.ok) {
    throw new Error(data.error || "Error del servidor (" + response.status + ").");
  }
  return data;
}

async function getJson(file) {
  const response = await fetch(API_BASE + file, { credentials: "same-origin" });
  return readResponse(response);
}

async function postJson(file, body) {
  const response = await fetch(API_BASE + file, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body)
  });
  return readResponse(response);
}