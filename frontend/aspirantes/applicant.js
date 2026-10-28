function ToggleEvent() {
    const sidebar = document.getElementById("sidebar");
    if (sidebar) {
        sidebar.classList.toggle("toggled");
    }
}

const form = document.getElementById("aspirant-form");
const registrationView = document.getElementById("registration-view");
const successView = document.getElementById("succes");

if (form) {
    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const btnSubmit = form.querySelector(".btn-submit");
        const nombreInput = form.querySelector('[name="nombre"]');
        const prepInput = form.querySelector('[name="preparatoria"]');
        const correoInput = form.querySelector('[name="correo"]');
        const carreraSelect = form.querySelector('[name="carrera"]');
        const privacyCheck = document.getElementById("privacy");

        const nombre = nombreInput.value.trim();
        const preparatoria = prepInput.value.trim();
        const correo = correoInput.value.trim();
        const carrera = carreraSelect.value;

        if (!nombre || !preparatoria || !correo || !carrera) {
            alert("Por favor completa todos los campos.");
            return;
        }
        const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regexEmail.test(correo)) {
            alert("Por favor ingresa un correo electrónico válido.");
            return;
        }
        if (privacyCheck && !privacyCheck.checked) {
            alert("Debes aceptar el aviso de privacidad.");
            return;
        }

        btnSubmit.disabled = true;
        btnSubmit.textContent = "Enviando...";

        const payload = {
            nombre,
            preparatoria,
            correo,
            carrera,
            privacy: privacyCheck ? privacyCheck.checked : true
        };

        try {
            const response = await fetch("../../backend/save_applicant.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (result.ok) {
                registrationView.style.display = "none";
                successView.style.display = "block";
            } else {
                alert("⚠️ " + (result.error ?? "Ocurrió un error. Intenta de nuevo."));
                btnSubmit.disabled = false;
                btnSubmit.textContent = "Registrarme";
            }

        } catch (err) {
            console.error("Error de red:", err);
            alert("Error de conexión. Verifica que el servidor XAMPP esté activo.");
            btnSubmit.disabled = false;
            btnSubmit.textContent = "Registrarme";
        }
    });
}