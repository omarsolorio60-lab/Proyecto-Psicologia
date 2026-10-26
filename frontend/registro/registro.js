(() => {
  'use strict';
  const $ = (s) => document.querySelector(s);
  const VISTAS = ['carga', 'acceso', 'registro', 'listo'];
  const API = '../../backend/cuenta/';   // endpoints del registro (ver backend/cuenta)
  const CAMPOS = ['matricula', 'nombre', 'apellido_paterno', 'apellido_materno', 'sexo', 'telefono', 'programa_id', 'semestre', 'grupo'];
  const NOMBRE_OK = /^\p{L}[\p{L}\p{M}\s.'’-]*$/u;
  const TIPOS_OK = ['image/jpeg', 'image/png', 'application/pdf'];

  let config = null;
  let archivo = null;
  let previewUrl = null;
  let enviando = false;

  // ---------- utilidades ----------
  function mostrar(vista) {
    VISTAS.forEach((v) => { $('#v-' + v).hidden = v !== vista; });
    const h1 = $('#v-' + vista + ' h1');
    if (h1) h1.focus({ preventScroll: true });
    window.scrollTo(0, 0);
  }

  async function api(ruta, opciones = {}) {
    let resp;
    try {
      resp = await fetch(API + ruta, { credentials: 'same-origin', ...opciones });
    } catch {
      const e = new Error('No hay conexión con el servidor. Revisa tu internet e inténtalo de nuevo.');
      e.status = 0;
      throw e;
    }
    let data = null;
    try { data = await resp.json(); } catch { /* sin cuerpo JSON */ }
    if (!resp.ok) {
      const e = new Error(data?.error?.message || 'Ocurrió un error. Inténtalo de nuevo.');
      e.status = resp.status;
      e.code = data?.error?.code;
      e.campos = data?.error?.campos;
      throw e;
    }
    return data;
  }

  function alerta(id, msg) {
    const el = $(id);
    el.textContent = msg || '';
    el.hidden = !msg;
  }

  function errorCampo(nombre, msg) {
    const p = $('#e-' + nombre);
    const input = $('#' + nombre);
    if (p) p.textContent = msg || '';
    if (input) {
      if (msg) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid');
    }
  }

  function limpiarErrores() {
    [...CAMPOS, 'horario'].forEach((c) => errorCampo(c, ''));
    alerta('#alerta-registro', '');
  }

  // ---------- acceso (Criterio 1) ----------
  function cargarScriptGoogle() {
    return new Promise((ok, fallo) => {
      if (window.google?.accounts?.id) return ok();
      const s = document.createElement('script');
      s.src = 'https://accounts.google.com/gsi/client';
      s.async = true;
      s.onload = ok;
      s.onerror = () => fallo(new Error('No se pudo cargar el acceso con Google. Revisa tu conexión.'));
      document.head.appendChild(s);
    });
  }

  async function iniciarGoogle() {
    $('#google-box').hidden = false;
    try {
      await cargarScriptGoogle();
    } catch (e) {
      alerta('#alerta-acceso', e.message);
      return;
    }
    window.google.accounts.id.initialize({
      client_id: config.googleClientId,
      callback: (r) => enviarCredencial(r.credential),
      hd: config.dominio,          // sugiere cuentas del dominio (la validación real es en el servidor)
      auto_select: false,
      ux_mode: 'popup',
    });
    window.google.accounts.id.renderButton($('#g-btn'), {
      theme: 'outline', size: 'large', shape: 'pill', text: 'continue_with', locale: 'es',
      width: Math.min(320, Math.max(220, $('#g-btn').clientWidth || 300)),
    });
  }

  async function enviarCredencial(valor, esPrueba = false) {
    alerta('#alerta-acceso', '');
    try {
      const r = await api('auth_google.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(esPrueba ? { dev_email: valor } : { credential: valor }),
      });
      await enrutar(r.estado);
    } catch (e) {
      alerta('#alerta-acceso', e.message);
    }
  }

  // ---------- formulario (Criterios 2, 3, 4) ----------
  function llenarSelect(sel, items, placeholder) {
    sel.replaceChildren(new Option(placeholder, ''));
    items.forEach(([v, t]) => sel.add(new Option(t, v)));
  }

  function prepararFormulario() {
    llenarSelect($('#sexo'), config.sexos.map((s) => [s.value, s.label]), 'Selecciona…');
    llenarSelect($('#programa_id'), config.programas.map((p) => [p.id, p.nombre]), 'Selecciona tu carrera…');
    llenarSelect($('#semestre'), Array.from({ length: config.semestreMax }, (_, i) => [i + 1, `${i + 1}.º semestre`]), 'Selecciona…');
    $('#max-mb').textContent = config.maxHorarioMB;
    $('#matricula').maxLength = config.matriculaDigitos;
    $('#matricula').placeholder = '1234567890'.slice(0, config.matriculaDigitos);
    // "Tomar foto" solo tiene sentido en pantallas táctiles (celular/tablet)
    $('#btn-camara').hidden = !window.matchMedia('(pointer: coarse)').matches;
  }

  function validarCliente(v) {
    const e = {};
    const digitos = config.matriculaDigitos;
    if (!v.matricula) e.matricula = 'Escribe tu matrícula.';
    else if (!new RegExp(`^\\d{${digitos}}$`).test(v.matricula)) e.matricula = `La matrícula debe tener ${digitos} dígitos.`;
    if (!v.nombre) e.nombre = 'Escribe tu nombre.';
    else if (!NOMBRE_OK.test(v.nombre)) e.nombre = 'Usa solo letras.';
    if (!v.apellido_paterno) e.apellido_paterno = 'Escribe tu apellido paterno.';
    else if (!NOMBRE_OK.test(v.apellido_paterno)) e.apellido_paterno = 'Usa solo letras.';
    if (v.apellido_materno && !NOMBRE_OK.test(v.apellido_materno)) e.apellido_materno = 'Usa solo letras.';
    if (!v.sexo) e.sexo = 'Selecciona una opción.';
    let tel = v.telefono.replace(/[\s().-]/g, '').replace(/^\+?52(?=\d{10}$)/, '');
    if (!v.telefono) e.telefono = 'Escribe tu teléfono.';
    else if (!/^\d{10}$/.test(tel)) e.telefono = 'El teléfono debe tener 10 dígitos.';
    if (!v.programa_id) e.programa_id = 'Selecciona tu carrera.';
    if (!v.semestre) e.semestre = 'Selecciona tu semestre.';
    if (!v.grupo) e.grupo = 'Escribe tu grupo.';
    else if (!/^[A-Za-z0-9-]{1,10}$/.test(v.grupo)) e.grupo = 'Usa solo letras, números o guion (máx. 10).';
    return e;
  }

  function aplicarErrores(errores) {
    let primero = null;
    for (const c of CAMPOS) {
      if (errores[c]) { errorCampo(c, errores[c]); primero ??= c; }
    }
    if (errores.horario) errorCampo('horario', errores.horario);
    if (primero) $('#' + primero).focus();
  }

  // Archivo de horario (Criterio 4)
  function elegirArchivo(file) {
    errorCampo('horario', '');
    if (!file) return;
    if (!TIPOS_OK.includes(file.type)) {
      errorCampo('horario', 'Usa un archivo JPG, PNG o PDF.');
      return;
    }
    const maxBytes = config.maxHorarioMB * 1024 * 1024;
    if (file.size > maxBytes) {
      errorCampo('horario', `El archivo pesa ${(file.size / 1024 / 1024).toFixed(1)} MB. El máximo es ${config.maxHorarioMB} MB.`);
      return;
    }
    archivo = file;
    $('#preview-name').textContent = file.name;
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    const img = $('#preview-img');
    if (file.type.startsWith('image/')) {
      previewUrl = URL.createObjectURL(file);
      img.src = previewUrl;
      img.hidden = false;
    } else {
      previewUrl = null;
      img.hidden = true;
      img.removeAttribute('src');
    }
    $('#preview').hidden = false;
  }

  function quitarArchivo() {
    archivo = null;
    $('#in-archivo').value = '';
    $('#in-camara').value = '';
    $('#preview').hidden = true;
    if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
    errorCampo('horario', '');
  }

  async function enviarRegistro(ev) {
    ev.preventDefault();
    if (enviando) return;
    limpiarErrores();

    const v = Object.fromEntries(CAMPOS.map((c) => [c, $('#' + c).value.trim()]));
    const errores = validarCliente(v);
    if (Object.keys(errores).length) { aplicarErrores(errores); return; }

    const fd = new FormData();
    CAMPOS.forEach((c) => fd.append(c, v[c]));
    if (archivo) fd.append('horario', archivo);

    enviando = true;
    const btn = $('#btn-enviar');
    btn.disabled = true;
    btn.textContent = 'Creando tu cuenta…';
    try {
      const r = await api('registro.php', { method: 'POST', body: fd });
      mostrarListo(r.estudiante, r.vinculado);
    } catch (e) {
      if (e.status === 401) {
        await enrutar('anonimo');
        alerta('#alerta-acceso', e.message);
      } else if (e.campos) {
        aplicarErrores(e.campos);
        alerta('#alerta-registro', e.message);
      } else {
        alerta('#alerta-registro', e.message);
        window.scrollTo(0, 0);
      }
    } finally {
      enviando = false;
      btn.disabled = false;
      btn.textContent = 'Crear mi cuenta';
    }
  }

  // ---------- pantalla final ----------
  function mostrarListo(est, vinculado) {
    if (!est) {
      // Cuenta que no es de estudiante (p. ej. una psicóloga): este registro no es para ella.
      mostrar('acceso');
      alerta('#alerta-acceso', 'Esta cuenta no tiene un perfil de estudiante. Si eres parte del área, entra desde "Soy administrador".');
      return;
    }
    $('#listo-texto').textContent = vinculado
      ? 'Vinculamos tu cuenta con tu registro existente en el área psicopedagógica.'
      : 'Ya puedes solicitar citas con el área de Orientación Educativa y Psicopedagógica.';
    const dl = document.createElement('dl');
    const filas = [
      ['Matrícula', est.matricula],
      ['Nombre', est.nombre],
      ['Carrera', est.programa || '—'],
      ['Semestre / grupo', `${est.semestre ?? '—'}.º · ${est.grupo || '—'}`],
      ['Horario', est.tiene_horario ? 'Subido ✓' : 'No subido'],
    ];
    for (const [k, val] of filas) {
      const dt = document.createElement('dt'); dt.textContent = k;
      const dd = document.createElement('dd'); dd.textContent = val;
      dl.append(dt, dd);
    }
    $('#resumen').replaceChildren(dl);
    mostrar('listo');
  }

  // ---------- enrutador ----------
  async function enrutar(estado) {
    if (estado === 'registrado') {
      const me = await api('me.php');
      return mostrarListo(me.estudiante, false);
    }
    if (estado === 'pendiente') {
      const me = await api('me.php');
      $('#correo').value = me.email;
      if (!$('#nombre').value) $('#nombre').value = me.nombre || '';
      mostrar('registro');
      return $('#matricula').focus();
    }
    mostrar('acceso');
  }

  async function iniciar() {
    try {
      config = await api('opciones.php');
      prepararFormulario();

      if (config.googleClientId) iniciarGoogle();
      if (config.devLogin) $('#dev-form').hidden = false;
      if (!config.googleClientId && !config.devLogin) alerta('#alerta-acceso', 'El acceso con Google no está configurado. Avisa al administrador.');

      $('#dev-form').addEventListener('submit', (e) => {
        e.preventDefault();
        enviarCredencial($('#dev-email').value, true);
      });
      $('#form-registro').addEventListener('submit', enviarRegistro);
      $('#btn-archivo').addEventListener('click', () => $('#in-archivo').click());
      $('#btn-camara').addEventListener('click', () => $('#in-camara').click());
      $('#in-archivo').addEventListener('change', (e) => elegirArchivo(e.target.files[0]));
      $('#in-camara').addEventListener('change', (e) => elegirArchivo(e.target.files[0]));
      $('#btn-quitar').addEventListener('click', quitarArchivo);
      CAMPOS.forEach((c) => $('#' + c).addEventListener('input', () => errorCampo(c, '')));
      $('#matricula').addEventListener('input', (e) => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, config.matriculaDigitos); });
      $('#btn-salir').addEventListener('click', async () => {
        await api('logout.php', { method: 'POST' }).catch(() => {});
        location.reload();
      });

      const me = await api('me.php');
      await enrutar(me.estado);
    } catch (e) {
      mostrar('acceso');
      alerta('#alerta-acceso', e.message);
    }
  }

  iniciar();
})();
