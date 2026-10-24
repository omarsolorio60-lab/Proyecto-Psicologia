function ToggleEvent() {
    document.getElementById("sidebar").classList.toggle("toggled");
}

document.getElementById("aspirant-form").addEventListener("submit", function(e) {
    e.preventDefault(); // Evita que la página se recargue
    
    // Como aun no hay base de datos, simulamos ocultando el formulario y mostrando el mensaje
    document.getElementById("registration-view").style.display = "none";
    document.getElementById("succes").style.display = "block";
});