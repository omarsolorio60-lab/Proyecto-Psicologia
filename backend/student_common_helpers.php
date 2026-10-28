<?php
// Validaciones compartidas de matrícula y nombres.
function matriculaError($s)
{
    if (!is_string($s) || trim($s) === '')
        return 'La matrícula es obligatoria.';
    $s = trim($s);
    if (preg_match('/\p{L}/u', $s))
        return 'La matrícula no puede contener letras.';
    if (preg_match('/[^0-9_-]/', $s))
        return 'La matrícula solo acepta números y los caracteres _ y -.';
    if (!preg_match('/^[0-9]/', $s))
        return 'La matrícula debe iniciar con un número.';
    if (strlen($s) < 6 || strlen($s) > 9)
        return 'La matrícula debe tener entre 6 y 9 caracteres.';
    return '';
}
function nameError($s, $label)
{
    if (!is_string($s) || trim($s) === '')
        return "$label es obligatorio.";
    $s = trim($s);
    if (preg_match('/\d/', $s))
        return "$label no puede contener números.";
    if (!preg_match('/^\p{L}[\p{L}\' .-]*$/u', $s))
        return "$label solo puede contener letras y espacios.";
    if (mb_strlen(preg_replace('/[^\p{L}]/u', '', $s)) < 2)
        return "$label debe tener al menos 2 letras.";
    return '';
}
