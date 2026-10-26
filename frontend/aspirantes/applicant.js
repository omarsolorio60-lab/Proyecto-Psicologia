// Control del menu lateral
function ToggleEvent() {
    const sidebar = document.getElementById("sidebar");
    if (sidebar) {
        sidebar.classList.toggle("toggled");
    }
}

// Guardar aspirantes en el navegador (mientras se integra PHP con la base de datos)
const STORAGE_KEY = 'sigep_aspirantes';

// Obtener aspirantes guardados del localStorage
function obtenerAspirantes() {
    const data = localStorage.getItem(STORAGE_KEY);
    return data ? JSON.parse(data) : [];
}

// Guardar nuevo aspirante en localStorage
function guardarAspiranteLocal(aspirante) {
    const aspirantes = obtenerAspirantes();
    aspirantes.push(aspirante);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(aspirantes));
    return aspirantes;
}

// Manejo del formulario de registro de aspirantes
const form = document.getElementById("aspirant-form");
const registrationView = document.getElementById("registration-view");
const successView = document.getElementById("succes");

if (form) {
    const V = window.SIGEP_VALID;
    const nombreInput = form.querySelector('[name="nombre"]');
    const prepInput = form.querySelector('[name="preparatoria"]');
    const correoInput = form.querySelector('[name="correo"]');
    const carreraSelect = form.querySelector('[name="carrera"]');
    const privacyInput = document.getElementById("privacy");
    const privacyBox = privacyInput.closest(".checkbox-group");

    // Validadores por campo (cada mensaje indica cual campo fallo)
    const vNombre = () => V.nombre(nombreInput.value, "El nombre completo");
    const vPrep = () => {
        const value = prepInput.value.trim();
        if (!value) return "La preparatoria de procedencia es obligatoria.";
        if (!/\p{L}/u.test(value)) return "Escribe el nombre de la preparatoria; puede incluir números, por ejemplo: Cobach Plantel 16.";
        return "";
    };
    const vCorreo = () => V.correo(correoInput.value);
    const vCarrera = () => V.requerido(carreraSelect.value, "Selecciona una carrera de interés.");
    const vPrivacy = () => (privacyInput.checked ? "" : "Debes aceptar el aviso de privacidad para continuar.");

    V.bindNombre(nombreInput, "El nombre completo");
    V.bindGeneric(prepInput, vPrep);
    V.bindGeneric(correoInput, vCorreo);
    V.bindGeneric(carreraSelect, vCarrera);
    privacyInput.addEventListener("change", () => {
        if (V.hasError(privacyInput, privacyBox)) V.clearError(privacyInput, privacyBox);
    });

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        // Todos los campos son obligatorios
        const errores = V.validateAll([
            { input: nombreInput, validate: vNombre },
            { input: prepInput, validate: vPrep },
            { input: correoInput, validate: vCorreo },
            { input: carreraSelect, validate: vCarrera },
            { input: privacyInput, validate: vPrivacy, anchor: privacyBox },
        ]);
        if (errores.length > 0) return;

        // Estructurar el objeto del aspirante
        const nuevoAspirante = {
            id: Date.now(),
            nombre: nombreInput.value.trim(),
            preparatoria: prepInput.value.trim(),
            correo: correoInput.value.trim(),
            carrera: carreraSelect.options[carreraSelect.selectedIndex].text,
            fecha_registro: new Date().toLocaleString("es-MX")
        };

        // Guardar en localStorage (persistencia en el navegador)
        const listaActualizada = guardarAspiranteLocal(nuevoAspirante);

        console.log("¡Aspirante registrado exitosamente!");
        console.table(listaActualizada);

        // Mostrar pantalla de exito
        registrationView.style.display = "none";
        successView.style.display = "block";
    });
}
