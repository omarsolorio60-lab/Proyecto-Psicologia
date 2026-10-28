function ToggleEvent(){const sb=document.getElementById('sidebar');if(sb)sb.classList.toggle('toggled')}
let step=1,motivo='Académico',modalidad='Presencial',hora='';
let verifiedStudent=null,cameFromLogin=false;
const V=window.SIGEP_VALID;
const $=s=>document.querySelector(s);
const dateInput=$('#fecha');
let availabilityLoaded=false;
async function refreshAvailability(){availabilityLoaded=false;hora='';document.querySelectorAll('.hour').forEach(b=>{b.disabled=true;b.classList.remove('selected');b.textContent=b.dataset.time+' · Consultando';});if(!dateInput.value)return;try{const r=await fetch('../../backend/availability.php?fecha='+encodeURIComponent(dateInput.value),{cache:'no-store'});const d=await r.json();if(!r.ok||!d.ok)throw Error(d.error||'Error de disponibilidad');availabilityLoaded=true;document.querySelectorAll('.hour').forEach(b=>{const left=Object.hasOwn(d.available,b.dataset.time)?d.available[b.dataset.time]:d.capacity;b.disabled=left<=0||new Date(dateInput.value+'T'+b.dataset.time+':00')<=new Date();b.textContent=b.dataset.time+(left<=0?' · Completo':' · '+left+' libre(s)');});}catch(e){alert('No se pudo consultar la disponibilidad: '+e.message);}}
dateInput.addEventListener('change',refreshAvailability);
function localDate(d){return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`}
dateInput.min=localDate(new Date());
// Solo hay tres horarios de atencion al dia
for(const t of ['09:00','11:00','13:00']){const b=document.createElement('button');b.type='button';b.className='hour';b.dataset.time=t;b.disabled=true;b.textContent=t;b.onclick=()=>{hora=t;document.querySelectorAll('.hour').forEach(x=>x.classList.remove('selected'));b.classList.add('selected');V.clearError($('#hours'))};$('#hours').append(b)}
function show(n){step=n;document.querySelectorAll('.step').forEach(el=>el.classList.toggle('active',Number(el.dataset.step)===n));$('#bar').style.width=`${Math.min(n,4)*25}%`;window.scrollTo(0,0)}
document.querySelectorAll('.choice').forEach(b=>b.onclick=()=>{motivo=b.dataset.value;document.querySelectorAll('.choice').forEach(x=>x.classList.remove('selected'));b.classList.add('selected');updateDetalleLabel()});
document.querySelectorAll('.mode').forEach(b=>b.onclick=()=>{modalidad=b.dataset.value;document.querySelectorAll('.mode').forEach(x=>x.classList.remove('selected'));b.classList.add('selected')});
function alumnoDatos(){return verifiedStudent || Object.fromEntries(new FormData($('#alumnoForm')).entries())}
const F={matricula:$('#matricula'),nombre:$('#nombre'),paterno:$('#apellido_paterno'),materno:$('#apellido_materno'),correo:$('#correo_institucional'),telefono:$('#telefono'),carrera:$('#carrera'),semestre:$('#semestre')};
const vAlumno={
 matricula:()=>V.matricula(F.matricula.value),
 nombre:()=>V.nombre(F.nombre.value,'El nombre'),
 paterno:()=>V.nombre(F.paterno.value,'El apellido paterno'),
 materno:()=>V.nombre(F.materno.value,'El apellido materno'),
 correo:()=>V.correo(F.correo.value,{uabc:true}),
 telefono:()=>V.telefono(F.telefono.value),
 carrera:()=>V.requerido(F.carrera.value,'La carrera es obligatoria.'),
 semestre:()=>V.semestre(F.semestre.value,20)
};
V.bindMatricula(F.matricula);V.bindNombre(F.nombre,'El nombre');V.bindNombre(F.paterno,'El apellido paterno');V.bindNombre(F.materno,'El apellido materno');V.bindTelefono(F.telefono);
V.bindGeneric(F.correo,vAlumno.correo);V.bindGeneric(F.carrera,vAlumno.carrera);V.bindGeneric(F.semestre,vAlumno.semestre);
function validateAlumno(){return V.validateAll(Object.keys(F).map(k=>({input:F[k],validate:vAlumno[k]}))).length===0}
$('#alumnoForm').addEventListener('input',()=>{$('#accountError').textContent=''});
$('#loginStudentForm').addEventListener('input',()=>{$('#loginError').textContent=''});
// Catalogo de carreras (tabla majors), con los mismos grupos que Aspirantes
async function loadMajors(){const sel=F.carrera;try{const r=await fetch('../../backend/majors_list.php',{cache:'no-store'});const d=await r.json();if(!r.ok||!d.ok)throw Error(d.error);const opt=n=>{const o=document.createElement('option');o.value=o.textContent=n;return o};const group=(label,items)=>{const g=document.createElement('optgroup');g.label=label;g.append(...items.map(opt));return g};const names=d.majors.map(m=>m.name);const first=document.createElement('option');first.value='';first.textContent='Selecciona una carrera';sel.replaceChildren(first,group('Ingenieria',names.slice(0,10)),group('Arquitectura y Diseño',names.slice(10)))}catch(e){if(sel&&sel.options&&sel.options[0])sel.options[0].textContent='No se pudieron cargar las carreras'}}
loadMajors();
// Un select no respeta readOnly, por eso la carrera se bloquea con disabled
function lockAlumnoForm(on){Object.values(F).forEach(i=>{i.readOnly=on;if(i.tagName==='SELECT')i.disabled=on});$('#lockedNote').hidden=!on;document.querySelector('[data-step="1"] .next').textContent=on?'Continuar':'Crear cuenta y continuar'}
function goToGate(){verifiedStudent=null;cameFromLogin=false;lockAlumnoForm(false);document.querySelectorAll('.step').forEach(x=>x.classList.remove('active'));$('#accountGate').hidden=false;$('#bar').style.width='0'}
const vDetalle=()=>motivo==='Otro'?V.requerido($('#detalle').value,'La descripción es obligatoria cuando eliges «Otro»: cuéntanos brevemente tu situación.'):'';
function validateDetalle(){return V.validateAll([{input:$('#detalle'),validate:vDetalle}]).length===0}
function updateDetalleLabel(){$('#detalleLabel').textContent=motivo==='Otro'?'Cuéntanos tu situación *':'Cuéntanos un poco más (opcional)';if(motivo!=='Otro')V.clearError($('#detalle'))}
V.bindGeneric($('#detalle'),vDetalle);
const vFecha=()=>!dateInput.value?'La fecha es obligatoria. Selecciona el día de tu cita.':'';
const vHora=()=>{if(!hora)return 'El horario es obligatorio. Selecciona una hora.';if(new Date(`${dateInput.value}T${hora}:00`)<=new Date())return 'El horario debe ser futuro. Selecciona otra hora.';return ''};
function validateHorario(){const errs=V.validateAll([{input:dateInput,validate:vFecha},{input:$('#hours'),validate:()=>dateInput.value?vHora():''}]);if(errs.length)return false;if(!availabilityLoaded){V.setError($('#hours'),'Espera a que se consulte la disponibilidad de ese día.');return false}return true}
dateInput.addEventListener('change',()=>V.clearError(dateInput));
function addSummary(k,v){const line=document.createElement('div');const bold=document.createElement('b');bold.textContent=k+': ';line.append(bold,document.createTextNode(v));$('#summary').append(line)}
document.querySelectorAll('.next').forEach(b=>b.onclick=async()=>{if(step===1&&!verifiedStudent){$('#accountError').textContent='';if(!validateAlumno())return;const btn=b;btn.disabled=true;try{const d=await accountRequest('register_student.php',alumnoDatos());verifiedStudent=d.student;lockAlumnoForm(true)}catch(e){$('#accountError').textContent=e.message;btn.disabled=false;return}btn.disabled=false;}
if(step===2){if(!validateDetalle())return}if(step===3){if(!validateHorario())return;const a=alumnoDatos();$('#summary').replaceChildren();addSummary('Alumno',`${a.nombre} ${a.apellido_paterno} ${a.apellido_materno}`.trim());addSummary('Matrícula',a.matricula);addSummary('Correo',a.correo_institucional);addSummary('Motivo',motivo);if($('#detalle').value.trim())addSummary('Descripción',$('#detalle').value.trim());addSummary('Modalidad',modalidad);addSummary('Fecha solicitada',dateInput.value);addSummary('Hora solicitada',hora)}show(step+1)});
document.querySelectorAll('.back').forEach(b=>b.onclick=()=>{if(step===1||(step===2&&cameFromLogin))goToGate();else show(step-1)});
$('#submit').onclick=async()=>{const button=$('#submit');button.disabled=true;$('#error').textContent='';try{const res=await fetch('../../backend/save_appointment.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:$('#csrf').value,alumno:alumnoDatos(),fecha:dateInput.value,hora,modalidad,motivo,detalle:$('#detalle').value.trim()})});const raw=await res.text();let data;try{data=JSON.parse(raw)}catch(_){throw new Error('El servidor devolvió una respuesta inválida. Revisa los errores de PHP y la conexión a MySQL.')} if(!res.ok||!data.ok)throw new Error(data.error||'No se pudo registrar la solicitud.');$('#result').textContent=`Folio: ${data.id_cita}. Estatus: Solicitada. Fecha propuesta: ${dateInput.value} ${hora}.`;show(5)}catch(e){$('#error').textContent=e.message;if(e.message.includes('horario'))refreshAvailability()}finally{button.disabled=false}};

// Acceso de estudiantes mediante registro o verificación de matrícula y correo.
$('#createAccount').onclick=()=>{$('#accountGate').hidden=true;verifiedStudent=null;cameFromLogin=false;lockAlumnoForm(false);show(1)};
$('#existingAccount').onclick=()=>{V.clearAll($('#loginStudentForm'));$('#loginError').textContent='';$('#loginDialog').showModal()};
$('#closeDialog').onclick=()=>$('#loginDialog').close();
V.bindMatricula($('#loginMatricula'));
V.bindGeneric($('#loginEmail'),()=>V.correo($('#loginEmail').value,{uabc:true}));
async function accountRequest(endpoint,payload){
 const r=await fetch('../../backend/'+endpoint,{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({...payload,csrf:$('#csrf').value})});
 const d=await r.json();if(!r.ok||!d.ok)throw Error(d.error||'No se pudo verificar la cuenta.');return d;
}
$('#loginStudentForm').onsubmit=async e=>{e.preventDefault();$('#loginError').textContent='';
const mIn=$('#loginMatricula'),eIn=$('#loginEmail');
const errs=V.validateAll([{input:mIn,validate:()=>V.matricula(mIn.value)},{input:eIn,validate:()=>V.correo(eIn.value,{uabc:true})}]);if(errs.length)return;
const email=eIn.value.trim();const matricula=mIn.value.trim();
const btn=$('#loginStudentForm button[type="submit"]');btn.disabled=true;try{const d=await accountRequest('verify_student.php',{matricula,correo_institucional:email});verifiedStudent=d.student;cameFromLogin=true;$('#loginDialog').close();$('#accountGate').hidden=true;show(2)}catch(err){$('#loginError').textContent=err.message}finally{btn.disabled=false}};
