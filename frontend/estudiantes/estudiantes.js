// Students list page (Figma 03a, HU-02).
// Order: 1) state  2) filtering  3) render functions  4) events  5) start.

// ===== 1) Page state =====
const PAGE_SIZE = 8;
const CURRENT_PSYCHOLOGIST = "A. López"; // TODO: take it from the session when login exists

const state = {
  view: "all",          // saved view (tab)
  search: "",
  filters: { priority: "", program: "", reason: "", psychologist: "", status: "" },
  sortBy: "priority",
  sortDirection: "asc",
  page: 1,
};

// Saved views: each one is just a rule over a student
const VIEWS = [
  { id: "all", label: "Todos", test: () => true },
  { id: "mine", label: "Mis alumnos", test: (s) => s.psychologist === CURRENT_PSYCHOLOGIST },
  { id: "priority", label: "Prioritarios", test: (s) => s.priority === "high" },
  { id: "referred", label: "Canalizados", test: (s) => s.status === "referred" },
  { id: "academic", label: "Académicos", test: (s) => s.category === "academic" },
  { id: "condition", label: "Con alguna condición", test: (s) => s.category === "condition" },
  { id: "closed", label: "Cerrados", test: (s) => s.status === "closed" },
];

const WORKFLOW = [
  { status: "first_attention", hint: "Sin seguimiento asignado", color: "var(--gold-500)" },
  { status: "follow_up", hint: "Con sesiones activas", color: "var(--green-600)" },
  { status: "referred", hint: "Con otra psicóloga o institución", color: "var(--green-900)" },
  { status: "closed", hint: "Caso concluido este periodo", color: "var(--gray-500)" },
];

const FILTER_LABELS = { priority: "Prioridad", program: "Carrera", reason: "Motivo", psychologist: "Psicóloga", status: "Estado" };

// Short helper
const $ = (selector) => document.querySelector(selector);

// ===== 2) Filtering and sorting =====
function normalize(text) {
  // Lowercase and remove accents so "maria" finds "María"
  return text.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}

function getFilteredStudents() {
  const view = VIEWS.find((v) => v.id === state.view);
  const search = normalize(state.search.trim());

  const result = students.filter((s) => {
    if (!view.test(s)) return false;
    if (search && !normalize(s.name).includes(search) && !s.enrollment.includes(search)) return false;
    for (const key in state.filters) {
      const value = state.filters[key];
      if (value && String(s[key]) !== value) return false;
    }
    return true;
  });

  return sortStudents(result);
}

function sortStudents(list) {
  const priorityOrder = { high: 0, medium: 1, stable: 2 };
  const direction = state.sortDirection === "asc" ? 1 : -1;

  return [...list].sort((a, b) => {
    let valueA = a[state.sortBy];
    let valueB = b[state.sortBy];
    if (state.sortBy === "priority") { valueA = priorityOrder[valueA]; valueB = priorityOrder[valueB]; }
    if (valueA === null) return 1;   // empty dates always at the end
    if (valueB === null) return -1;
    if (valueA < valueB) return -1 * direction;
    if (valueA > valueB) return 1 * direction;
    return 0;
  });
}

// ===== 3) Render functions =====
function getInitials(name) {
  return name.split(" ").slice(0, 2).map((word) => word[0]).join("");
}

function formatDate(value) {
  if (!value) return null;
  const [date, time] = value.split(" ");
  const [, month, day] = date.split("-");
  return `${day}/${month} · ${time}`;
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function renderWorkflow() {
  $("#workflow").innerHTML = WORKFLOW.map((step) => {
    const count = students.filter((s) => s.status === step.status).length;
    const pressed = state.filters.status === step.status;
    return `
      <button type="button" class="workflow-card" data-status="${step.status}" aria-pressed="${pressed}">
        <span class="workflow-card__bar" style="background:${step.color}"></span>
        <span>
          <span class="workflow-card__count">${count}</span>
          <span class="workflow-card__label">${STATUS_LABELS[step.status]}</span>
          <small>${step.hint}</small>
        </span>
      </button>`;
  }).join("");
}

function renderTabs() {
  $("#tabs").innerHTML = VIEWS.map((view) => {
    const count = students.filter(view.test).length;
    const selected = view.id === state.view;
    return `<button type="button" class="tab" role="tab" data-view="${view.id}" aria-selected="${selected}">
              ${view.label}<span class="tab__count">${count}</span>
            </button>`;
  }).join("");
}

function renderFilterChips() {
  const chips = [];
  for (const key in state.filters) {
    const value = state.filters[key];
    if (!value) continue;
    const shown = key === "status" ? STATUS_LABELS[value] : key === "priority" ? PRIORITY_LABELS[value] : value;
    chips.push(`<button type="button" class="chip" data-key="${key}" aria-label="Quitar filtro ${FILTER_LABELS[key]}">${FILTER_LABELS[key]}: ${escapeHtml(shown)} ✕</button>`);
  }
  $("#filter-chips").innerHTML = chips.join("");

  // Highlight the selects that have a value
  for (const key in state.filters) {
    const select = $(`#filter-${key}`);
    select.value = state.filters[key];
    select.classList.toggle("is-active", state.filters[key] !== "");
  }
}

function renderTable(list) {
  const start = (state.page - 1) * PAGE_SIZE;
  const pageItems = list.slice(start, start + PAGE_SIZE);

  if (pageItems.length === 0) {
    $("#students-body").innerHTML = `<tr class="empty-row"><td colspan="8">Ningún alumno coincide con estos filtros.</td></tr>`;
    return;
  }

  $("#students-body").innerHTML = pageItems.map((s) => `
    <tr data-id="${s.id}" tabindex="0">
      <td>
        <div class="student-cell">
          <span class="avatar ${s.priority === "high" ? "avatar--high" : ""}">${getInitials(s.name)}</span>
          <div><strong>${escapeHtml(s.name)}</strong><small>${s.enrollment}</small></div>
        </div>
      </td>
      <td>${escapeHtml(s.program)} · ${s.semester}</td>
      <td><span class="badge badge--${s.priority}">${PRIORITY_LABELS[s.priority]}</span></td>
      <td>${escapeHtml(s.reason)}</td>
      <td>${escapeHtml(s.psychologist)}</td>
      <td>${formatDate(s.lastSession) ?? '<span class="muted">—</span>'}</td>
      <td>${formatDate(s.nextAppointment) ?? '<span class="muted">Sin cita</span>'}</td>
      <td><span class="status status--${s.status}">${STATUS_LABELS[s.status]}</span></td>
    </tr>`).join("");
}

function renderPagination(total) {
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (state.page > pages) state.page = pages;
  const first = total === 0 ? 0 : (state.page - 1) * PAGE_SIZE + 1;
  const last = Math.min(state.page * PAGE_SIZE, total);
  $("#page-info").textContent = `Mostrando ${first}–${last} de ${total}`;

  let buttons = "";
  for (let p = 1; p <= pages; p++) {
    const current = p === state.page ? ' aria-current="page"' : "";
    buttons += `<button type="button" class="page-button" data-page="${p}"${current}>${p}</button>`;
  }
  $("#page-buttons").innerHTML = buttons;
}

function renderSortHeaders() {
  document.querySelectorAll(".sort").forEach((button) => {
    if (button.dataset.sort === state.sortBy) button.dataset.direction = state.sortDirection;
    else delete button.dataset.direction;
  });
}

function render() {
  const list = getFilteredStudents();
  $("#total-students").textContent = students.length;
  $("#results-count").textContent = `${list.length} resultado${list.length === 1 ? "" : "s"}`;
  renderWorkflow();
  renderTabs();
  renderFilterChips();
  renderPagination(list.length);
  renderTable(list);
  renderSortHeaders();
}

// Fill the <select> options from the catalogs
function fillSelect(select, values, firstOption) {
  const options = values.map((v) => `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`).join("");
  select.innerHTML = (firstOption ? `<option value="">${firstOption}</option>` : "") + options;
}

// ===== 4) Events =====

// Search: wait 300 ms after the user stops typing
let searchTimer;
$("#search").addEventListener("input", (event) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    state.search = event.target.value;
    state.page = 1;
    render();
  }, 300);
});

// Filter selects
for (const key in state.filters) {
  $(`#filter-${key}`).addEventListener("change", (event) => {
    state.filters[key] = event.target.value;
    state.page = 1;
    render();
  });
}

// Workflow cards toggle the status filter
$("#workflow").addEventListener("click", (event) => {
  const card = event.target.closest(".workflow-card");
  if (!card) return;
  state.filters.status = state.filters.status === card.dataset.status ? "" : card.dataset.status;
  state.page = 1;
  render();
});

// Tabs
$("#tabs").addEventListener("click", (event) => {
  const tab = event.target.closest(".tab");
  if (!tab) return;
  state.view = tab.dataset.view;
  state.page = 1;
  render();
});

// Remove one filter chip
$("#filter-chips").addEventListener("click", (event) => {
  const chip = event.target.closest(".chip");
  if (!chip) return;
  state.filters[chip.dataset.key] = "";
  render();
});

// Clear everything
$("#clear-filters").addEventListener("click", () => {
  for (const key in state.filters) state.filters[key] = "";
  state.search = "";
  state.view = "all";
  $("#search").value = "";
  render();
});

// Sort by column
document.querySelectorAll(".sort").forEach((button) => {
  button.addEventListener("click", () => {
    if (state.sortBy === button.dataset.sort) {
      state.sortDirection = state.sortDirection === "asc" ? "desc" : "asc";
    } else {
      state.sortBy = button.dataset.sort;
      state.sortDirection = "asc";
    }
    render();
  });
});

// Pagination
$("#page-buttons").addEventListener("click", (event) => {
  const button = event.target.closest(".page-button");
  if (!button) return;
  state.page = Number(button.dataset.page);
  render();
});

// Open a student record (page not built yet)
function openRecord(id) {
  window.location.href = `expediente.html?id=${id}`; // TODO: create expediente.html (Figma 03)
}
$("#students-body").addEventListener("click", (event) => {
  const row = event.target.closest("tr[data-id]");
  if (row) openRecord(row.dataset.id);
});
$("#students-body").addEventListener("keydown", (event) => {
  const row = event.target.closest("tr[data-id]");
  if (row && event.key === "Enter") openRecord(row.dataset.id);
});

// Export (placeholder until reports exist)
$("#export-button").addEventListener("click", () => {
  alert("La exportación estará disponible en el sprint de Reportes.");
});

// ===== Register student modal (HU-02) =====
const modal = $("#register-modal");
const form = $("#register-form");

$("#register-button").addEventListener("click", () => {
  form.reset();
  hideFormError();
  modal.showModal();
});

modal.querySelectorAll("[data-close]").forEach((button) => {
  button.addEventListener("click", () => modal.close());
});

function showFormError(message) {
  $("#form-error").textContent = message;
  $("#form-error").hidden = false;
}

function hideFormError() {
  $("#form-error").hidden = true;
  form.querySelectorAll(".field--error").forEach((f) => f.classList.remove("field--error"));
}

form.addEventListener("submit", (event) => {
  event.preventDefault();
  hideFormError();

  // Criteria 2 and 3: required fields must be filled before saving
  const missing = [...form.querySelectorAll("[required]")].filter((input) => input.value.trim() === "");
  if (missing.length > 0) {
    missing.forEach((input) => input.closest(".field").classList.add("field--error"));
    showFormError("Completa los campos obligatorios.");
    missing[0].focus();
    return;
  }

  const data = Object.fromEntries(new FormData(form));

  if (!/^\d{7}$/.test(data.enrollment)) { // misma regla que config/config.php (MATRICULA_DIGITOS)
    $("#enrollment").closest(".field").classList.add("field--error");
    showFormError("La matrícula debe tener 7 dígitos.");
    return;
  }
  if (students.some((s) => s.enrollment === data.enrollment)) {
    $("#enrollment").closest(".field").classList.add("field--error");
    showFormError("Esta matrícula ya está registrada.");
    return;
  }

  // Criterion 1: save the student's general data
  students.push({
    id: Date.now(),
    enrollment: data.enrollment,
    name: data.fullName.trim(),
    program: data.program,
    semester: Number(data.semester),
    priority: "stable",
    reason: data.reason,
    psychologist: data.psychologist,
    lastSession: null,
    nextAppointment: null,
    status: "first_attention",
    category: data.reason === "Académico" ? "academic" : "emotional",
  });

  modal.close();
  state.view = "all";
  state.filters.status = "first_attention";
  render();
});

// ===== 5) Start =====
fillSelect($("#filter-program"), PROGRAMS, "Carrera");
fillSelect($("#filter-reason"), REASONS, "Motivo");
fillSelect($("#filter-psychologist"), PSYCHOLOGISTS, "Psicóloga");
fillSelect($("#program"), PROGRAMS, "Selecciona");
fillSelect($("#reason"), REASONS, "Selecciona");
fillSelect($("#psychologist"), PSYCHOLOGISTS, "Selecciona");
render();