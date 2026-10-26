// Validaciones compartidas del sistema SIGEP (aspirantes, citas y estudiantes).
// Cada validador devuelve "" si el valor es correcto, o el mensaje de error
// indicando exactamente qué campo falló.
(function () {
  // ===== Reglas =====
  var MATRICULA_MIN = 6;
  var MATRICULA_MAX = 9; // "menos de 10" caracteres
  var RE_MATRICULA = /^\d[\d_-]*$/; // números; único especiales permitidos: _ y -
  var RE_NOMBRE = /^\p{L}[\p{L}' .-]*$/u;
  var RE_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var RE_UABC = /^[^\s@]+@uabc\.edu\.mx$/i;

  function trim(v) { return String(v == null ? "" : v).trim(); }

  // ===== Validadores =====
  function matricula(value) {
    var v = trim(value);
    if (!v) return "La matrícula es obligatoria.";
    if (/\p{L}/u.test(v)) return "La matrícula no puede contener letras.";
    if (/[^0-9_-]/.test(v)) return "La matrícula solo acepta números y los caracteres _ y -.";
    if (!RE_MATRICULA.test(v)) return "La matrícula debe iniciar con un número.";
    if (v.length < MATRICULA_MIN || v.length > MATRICULA_MAX)
      return "La matrícula debe tener entre " + MATRICULA_MIN + " y " + MATRICULA_MAX + " caracteres.";
    return "";
  }

  // label: "El nombre", "El apellido paterno", etc.
  function nombre(value, label) {
    label = label || "El nombre";
    var v = trim(value);
    if (!v) return label + " es obligatorio.";
    if (/\d/.test(v)) return label + " no puede contener números.";
    if (!RE_NOMBRE.test(v)) return label + " solo puede contener letras y espacios.";
    if (v.replace(/[^\p{L}]/gu, "").length < 2) return label + " debe tener al menos 2 letras.";
    return "";
  }

  function correo(value, opts) {
    var v = trim(value);
    if (!v) return "El correo electrónico es obligatorio.";
    if (!RE_EMAIL.test(v)) return "El correo electrónico no es válido (ejemplo: nombre@dominio.com).";
    if (opts && opts.uabc && !RE_UABC.test(v)) return "El correo institucional debe terminar en @uabc.edu.mx.";
    return "";
  }

  function telefono(value) {
    var v = trim(value);
    if (!v) return "El teléfono es obligatorio.";
    if (/\D/.test(v)) return "El teléfono solo puede contener números.";
    if (v.length !== 10) return "El teléfono debe tener 10 dígitos.";
    return "";
  }

  function semestre(value, max) {
    max = max || 20;
    var v = trim(value);
    if (!v) return "El semestre es obligatorio.";
    if (!/^\d+$/.test(v) || +v < 1 || +v > max) return "El semestre debe ser un número entre 1 y " + max + ".";
    return "";
  }

  // Campo de texto o select obligatorio. message: "La carrera es obligatoria."
  function requerido(value, message) {
    return trim(value) ? "" : message;
  }

  // ===== Mensajes en pantalla =====
  function errorBox(anchor) {
    var box = anchor.nextElementSibling;
    return box && box.classList.contains("field-error") ? box : null;
  }

  function setError(input, message, anchor) {
    anchor = anchor || input;
    var box = errorBox(anchor);
    if (!box) {
      box = document.createElement("p");
      box.className = "field-error";
      box.setAttribute("role", "alert");
      anchor.insertAdjacentElement("afterend", box);
    }
    box.textContent = message;
    input.classList.add("input-invalid");
    input.setAttribute("aria-invalid", "true");
  }

  function clearError(input, anchor) {
    anchor = anchor || input;
    var box = errorBox(anchor);
    if (box) box.remove();
    input.classList.remove("input-invalid");
    input.removeAttribute("aria-invalid");
  }

  function hasError(input, anchor) {
    return !!errorBox(anchor || input);
  }

  // Muestra u oculta el error de un campo según su validador. Devuelve true si es válido.
  function apply(input, validator, anchor) {
    var msg = validator();
    if (msg) setError(input, msg, anchor); else clearError(input, anchor);
    return !msg;
  }

  // Valida varios campos. items: [{ input, validate, anchor? }]
  // Marca todos los inválidos, enfoca el primero y devuelve la lista de mensajes.
  function validateAll(items) {
    var errors = [];
    var first = null;
    items.forEach(function (it) {
      var msg = it.validate();
      if (msg) {
        setError(it.input, msg, it.anchor);
        errors.push(msg);
        if (!first) first = it.input;
      } else {
        clearError(it.input, it.anchor);
      }
    });
    if (first && first.focus) first.focus();
    return errors;
  }

  function clearAll(container) {
    container.querySelectorAll(".field-error").forEach(function (b) { b.remove(); });
    container.querySelectorAll(".input-invalid").forEach(function (i) {
      i.classList.remove("input-invalid");
      i.removeAttribute("aria-invalid");
    });
  }

  // ===== Filtros al escribir (bloquean caracteres inválidos y avisan) =====
  function bindMatricula(input) {
    input.removeAttribute("maxlength");
    input.setAttribute("autocomplete", "off");
    input.addEventListener("input", function () {
      var raw = input.value;
      var clean = raw.replace(/[^0-9_-]/g, "");
      var msg = "";
      if (/\p{L}/u.test(raw)) msg = "La matrícula no puede contener letras. Solo se permiten números y los caracteres _ y -.";
      else if (clean !== raw) msg = "La matrícula solo acepta números y los caracteres _ y -.";
      if (clean.length > MATRICULA_MAX) {
        clean = clean.slice(0, MATRICULA_MAX);
        msg = "La matrícula debe tener menos de 10 caracteres (máximo " + MATRICULA_MAX + ").";
      }
      if (clean !== raw) input.value = clean;
      if (msg) setError(input, msg); else if (hasError(input)) clearError(input);
    });
    input.addEventListener("blur", function () {
      if (input.value || hasError(input)) apply(input, function () { return matricula(input.value); });
    });
  }

  function bindNombre(input, label) {
    input.addEventListener("input", function () {
      var raw = input.value;
      var clean = raw.replace(/[^\p{L}' .-]/gu, "");
      if (clean !== raw) {
        input.value = clean;
        setError(input, /\d/.test(raw)
          ? label + " no puede contener números."
          : label + " solo puede contener letras y espacios.");
      } else if (hasError(input)) {
        clearError(input);
      }
    });
    input.addEventListener("blur", function () {
      if (input.value || hasError(input)) apply(input, function () { return nombre(input.value, label); });
    });
  }

  function bindTelefono(input) {
    input.removeAttribute("maxlength");
    input.addEventListener("input", function () {
      var raw = input.value;
      var clean = raw.replace(/\D/g, "").slice(0, 10);
      if (clean !== raw) {
        input.value = clean;
        setError(input, /\D/.test(raw) ? "El teléfono solo puede contener números." : "El teléfono debe tener 10 dígitos.");
      } else if (hasError(input)) {
        clearError(input);
      }
    });
    input.addEventListener("blur", function () {
      if (input.value || hasError(input)) apply(input, function () { return telefono(input.value); });
    });
  }

  // Validación al salir del campo y al corregirlo (correo, selects, etc.)
  function bindGeneric(input, validator, anchor) {
    var run = function () { apply(input, validator, anchor); };
    input.addEventListener("blur", function () { if (trim(input.value) || hasError(input, anchor)) run(); });
    // Al volver a escribir o cambiar la opcion, el error desaparece; se revalida al salir del campo o al enviar
    input.addEventListener("input", function () { if (hasError(input, anchor)) clearError(input, anchor); });
    input.addEventListener("change", function () { if (hasError(input, anchor)) clearError(input, anchor); });
  }

  window.SIGEP_VALID = {
    MATRICULA_MIN: MATRICULA_MIN,
    MATRICULA_MAX: MATRICULA_MAX,
    matricula: matricula,
    nombre: nombre,
    correo: correo,
    telefono: telefono,
    semestre: semestre,
    requerido: requerido,
    setError: setError,
    clearError: clearError,
    clearAll: clearAll,
    hasError: hasError,
    apply: apply,
    validateAll: validateAll,
    bindMatricula: bindMatricula,
    bindNombre: bindNombre,
    bindTelefono: bindTelefono,
    bindGeneric: bindGeneric,
  };
})();
