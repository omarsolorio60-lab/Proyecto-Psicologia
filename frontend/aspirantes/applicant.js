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
    form.addEventListener("submit", function (e) {
        e.preventDefault();

        // 1. Obtener valores de los campos
        const nombreInput = form.querySelector('[name="nombre"]');
        const prepInput = form.querySelector('[name="preparatoria"]');
        const correoInput = form.querySelector('[name="correo"]');
        const carreraSelect = form.querySelector('[name="carrera"]');

        const nombre = nombreInput.value.trim();
        const preparatoria = prepInput.value.trim();
        const correo = correoInput.value.trim();
        const carrera = carreraSelect.options[carreraSelect.selectedIndex].text;

        // Validacion basica
        if (!nombre || !preparatoria || !correo || !carreraSelect.value) {
            alert("Por favor completa todos los campos.");
            return;
        }

        // Validacion de correo electrónico
        const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regexEmail.test(correo)) {
            alert("Por favor ingresa un correo electrónico válido.");
            return;
        }

        // Estructurar el objeto del aspirante
        const nuevoAspirante = {
            id: Date.now(),
            nombre: nombre,
            preparatoria: preparatoria,
            correo: correo,
            carrera: carrera,
            fecha_registro: new Date().toLocaleString("es-MX")
        };

        // 4. Guardar en localStorage (Persistencia en el navegador)
        const listaActualizada = guardarAspiranteLocal(nuevoAspirante);

        console.log("¡Aspirante registrado exitosamente!");
        console.table(listaActualizada);

        // 5. Mostrar pantalla de exito
        registrationView.style.display = "none";
        successView.style.display = "block";
    });
}