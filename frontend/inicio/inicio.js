// Panel de inicio de la psicologa (HU-15).
// Los datos llegan de backend/dashboard.php por medio de shared/api.js.

const MODE_LABELS = { in_person: "Presencial", online: "En l\u00ednea" };

const REASON_LABELS = {
  academic: "Acad\u00e9mico",
  personal: "Personal o emocional",
  vocational: "Orientaci\u00f3n vocacional",
  excuse: "Justificante"
};

// Datos que se reciben del PHP; se guardan para usarlos al canalizar
let dashboard = null;

// ===== Utilidades =====
function createElement(tag, className, text) {
  const element = document.createElement(tag);
  if (className) {
    element.className = className;
  }
  if (text !== undefined) {
    element.textContent = text;
  }
  return element;
}

// Carrera corta para que la fila no se corte tanto
function shortProgram(program) {
  return program.replace("Ingenieria", "Ing.");
}

// "2026-10-13 09:00" -> "hace 13 dias"
function timeAgo(dateText) {
  if (!dateText) {
    return "Sin sesiones previas";
  }
  const date = new Date(dateText.replace(" ", "T"));
  const days = Math.floor((Date.now() - date.getTime()) / (1000 * 60 * 60 * 24));
  if (days <= 0) {
    return "hoy";
  }
  if (days === 1) {
    return "hace 1 d\u00eda";
  }
  return "hace " + days + " d\u00edas";
}

function showEmptyMessage(container, text) {
  container.innerHTML = "";
  container.appendChild(createElement("p", "empty-state", text));
}

// ===== Encabezado (criterio 1) =====
function renderHeader(staff) {
  const today = new Date();
  let dateText = today.toLocaleDateString("es-MX", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric"
  });
  dateText = dateText.charAt(0).toUpperCase() + dateText.slice(1);
  document.getElementById("current-date").textContent = dateText;

  const hour = today.getHours();
  let greeting = "Buenas noches";
  if (hour < 12) {
    greeting = "Buenos d\u00edas";
  } else if (hour < 19) {
    greeting = "Buenas tardes";
  }
  document.getElementById("greeting-text").textContent = greeting + ", ";
  document.getElementById("user-greeting").textContent = staff.name;
}

// ===== Tarjetas de indicadores (criterio 1) =====
function renderStats(stats) {
  document.getElementById("stat-citas-hoy").textContent = stats.appointmentsToday;
  document.getElementById("stat-citas-detalle").textContent =
    stats.inPerson + " presenciales \u00b7 " + stats.online + " en l\u00ednea";
  document.getElementById("stat-alumnos-seguimiento").textContent = stats.activeStudents;
  document.getElementById("stat-casos-prioritarios").textContent = stats.priorityCases;
  document.getElementById("stat-justificantes").textContent = stats.pendingExcuses;
}

// ===== Citas de hoy (criterio 2) =====
function renderAppointments(appointments) {
  const container = document.getElementById("citas-container");

  if (appointments.length === 0) {
    showEmptyMessage(container, "No tienes citas para hoy.");
    return;
  }

  container.innerHTML = "";
  appointments.forEach(function (appointment) {
    const row = createElement("div", "list-item");
    row.appendChild(createElement("div", "time", appointment.time));

    const info = createElement("div", "info");
    info.appendChild(createElement("h4", "", appointment.name));
    info.appendChild(createElement("p", "",
      appointment.enrollment + " \u00b7 " + shortProgram(appointment.program) + " \u00b7 " + appointment.reason));
    row.appendChild(info);

    row.appendChild(createElement("div", "tag", MODE_LABELS[appointment.mode]));
    container.appendChild(row);
  });
}

// ===== Casos prioritarios (criterio 3) =====
function createPriorityBadge(priority) {
  const isHigh = priority === "high";
  const badge = createElement("span", "priority-badge " + (isHigh ? "priority-high" : "priority-med"));
  badge.appendChild(createElement("i", "circle"));
  badge.appendChild(document.createTextNode(isHigh ? " Prioridad alta" : " Seguimiento"));
  return badge;
}

function renderPriorityCases(cases) {
  const container = document.getElementById("casos-container");

  if (cases.length === 0) {
    showEmptyMessage(container, "No hay casos prioritarios.");
    return;
  }

  container.innerHTML = "";
  cases.forEach(function (item) {
    const row = createElement("div", "case-item");

    // El PHP solo manda iniciales y matricula por confidencialidad
    const info = createElement("div", "info");
    info.appendChild(createElement("h4", "", item.initials + " \u00b7 " + item.enrollment));
    info.appendChild(createElement("p", "",
      (REASON_LABELS[item.reason] || item.reason) + " \u00b7 " + timeAgo(item.lastSession)));
    row.appendChild(info);

    const actions = createElement("div", "actions");
    actions.appendChild(createPriorityBadge(item.priority));

    const button = createElement("button", "btn-outline");
    button.type = "button";
    button.appendChild(createElement("i", "row"));
    button.appendChild(document.createTextNode(" Canalizar"));
    button.addEventListener("click", function () {
      referCase(item, button);
    });
    actions.appendChild(button);

    row.appendChild(actions);
    container.appendChild(row);
  });
}

// ===== Canalizar (criterio 4) =====
async function referCase(item, button) {
  const message = document.getElementById("home-message");
  const target = dashboard.otherStaff[0];

  if (!target) {
    message.textContent = "No hay otra psic\u00f3loga activa para canalizar el caso.";
    return;
  }

  const confirmed = window.confirm(
    "\u00bfCanalizar el caso " + item.initials + " con " + target.name + "?");
  if (!confirmed) {
    return;
  }

  button.disabled = true;
  try {
    const result = await postJson("refer_case.php", {
      caseId: item.caseId,
      toPsychologistId: target.id
    });
    message.textContent = "Caso " + item.initials + " canalizado con " + result.to + ".";
    await loadDashboard();
  } catch (error) {
    message.textContent = error.message;
    button.disabled = false;
  }
}

// ===== Inicio =====
async function loadDashboard() {
  dashboard = await getJson("dashboard.php");
  renderHeader(dashboard.staff);
  renderStats(dashboard.stats);
  renderAppointments(dashboard.appointments);
  renderPriorityCases(dashboard.cases);
}

async function init() {
  try {
    await loadDashboard();
  } catch (error) {
    showEmptyMessage(document.getElementById("citas-container"), "No se pudo cargar el panel: " + error.message);
    showEmptyMessage(document.getElementById("casos-container"), "Revisa que la p\u00e1gina est\u00e9 abierta desde XAMPP.");
  }
}

init();
