// Mock data while there is no PHP backend.
// Later, students.js will get this same structure from backend/students_list.php.

const PROGRAMS = [
  "Bioingenieria",
  "Ingenieria en Software y Tecnologias Emergentes",
  "Ingenieria Aeroespacial",
  "Ingenieria Civil",
  "Ingenieria Electrica",
  "Ingenieria en Energias Renovables",
  "Ingenieria Industrial",
  "Ingenieria Mecanica",
  "Ingenieria Mecatronica",
  "Ingenieria en Semiconductores y Microelectronica",
  "Arquitectura",
  "Diseño Grafico",
  "Diseño Industrial",
];
const REASONS = ["Académico", "Personal o emocional", "Orientación vocacional", "Justificante"];
const PSYCHOLOGISTS = ["A. López", "R. Méndez"];

const STATUS_LABELS = {
  first_attention: "Primera atención",
  follow_up: "En seguimiento",
  referred: "Canalizado",
  closed: "Cerrado",
};

const PRIORITY_LABELS = { high: "Prioridad alta", medium: "Prioridad media", stable: "Estable" };

// lastSession / nextAppointment use "YYYY-MM-DD HH:MM" or null
let students = [
  { id: 1, enrollment: "1234567", name: "Juan Pérez Martínez", program: "Ingenieria en Software y Tecnologias Emergentes", semester: 5, priority: "high", reason: "Académico", psychologist: "A. López", lastSession: "2026-10-13 09:00", nextAppointment: "2026-10-27 13:00", status: "follow_up", category: "academic" },
  { id: 2, enrollment: "1251234", name: "Ana Castro Vega", program: "Ingenieria Mecatronica", semester: 3, priority: "high", reason: "Personal o emocional", psychologist: "A. López", lastSession: "2026-10-15 13:00", nextAppointment: "2026-10-28 12:30", status: "referred", category: "condition" },
  { id: 3, enrollment: "1240012", name: "Ricardo Gómez León", program: "Ingenieria Industrial", semester: 7, priority: "high", reason: "Personal o emocional", psychologist: "A. López", lastSession: "2026-10-12 09:30", nextAppointment: "2026-10-26 09:30", status: "follow_up", category: "condition" },
  { id: 4, enrollment: "1245789", name: "María Sánchez Ruiz", program: "Bioingenieria", semester: 3, priority: "medium", reason: "Académico", psychologist: "A. López", lastSession: "2026-10-16 10:30", nextAppointment: "2026-10-29 10:30", status: "follow_up", category: "academic" },
  { id: 5, enrollment: "1247755", name: "Natalia Bravo Cruz", program: "Ingenieria Electrica", semester: 5, priority: "medium", reason: "Orientación vocacional", psychologist: "A. López", lastSession: "2026-10-10 09:00", nextAppointment: "2026-10-30 09:00", status: "follow_up", category: "academic" },
  { id: 6, enrollment: "1249930", name: "Fernanda Díaz Hernández", program: "Bioingenieria", semester: 5, priority: "medium", reason: "Personal o emocional", psychologist: "R. Méndez", lastSession: "2026-10-08 11:00", nextAppointment: "2026-10-28 11:00", status: "referred", category: "emotional" },
  { id: 7, enrollment: "1239901", name: "Luis Ortega Díaz", program: "Ingenieria en Software y Tecnologias Emergentes", semester: 5, priority: "medium", reason: "Académico", psychologist: "A. López", lastSession: "2026-10-19 12:00", nextAppointment: null, status: "first_attention", category: "academic" },
  { id: 8, enrollment: "1228870", name: "Diego Flores Mora", program: "Ingenieria Industrial", semester: 7, priority: "stable", reason: "Académico", psychologist: "A. López", lastSession: "2026-10-19 16:00", nextAppointment: "2026-10-30 14:00", status: "follow_up", category: "academic" },
  { id: 9, enrollment: "1260011", name: "Karla López Reyes", program: "Ingenieria Mecatronica", semester: 1, priority: "stable", reason: "Justificante", psychologist: "R. Méndez", lastSession: "2026-10-17 08:00", nextAppointment: null, status: "first_attention", category: "academic" },
  { id: 10, enrollment: "1255501", name: "Emilio Vargas Ríos", program: "Ingenieria en Software y Tecnologias Emergentes", semester: 3, priority: "stable", reason: "Personal o emocional", psychologist: "R. Méndez", lastSession: "2026-10-02 10:00", nextAppointment: null, status: "closed", category: "emotional" },
  { id: 11, enrollment: "1243318", name: "Sofía Herrera Luna", program: "Bioingenieria", semester: 7, priority: "medium", reason: "Personal o emocional", psychologist: "R. Méndez", lastSession: "2026-10-14 15:00", nextAppointment: "2026-10-28 15:00", status: "follow_up", category: "emotional" },
  { id: 12, enrollment: "1236644", name: "Mateo Cano Ibarra", program: "Ingenieria en Software y Tecnologias Emergentes", semester: 9, priority: "stable", reason: "Orientación vocacional", psychologist: "A. López", lastSession: "2026-09-29 11:00", nextAppointment: null, status: "closed", category: "academic" },
  { id: 13, enrollment: "1258872", name: "Regina Salas Ortiz", program: "Ingenieria Industrial", semester: 1, priority: "stable", reason: "Académico", psychologist: "R. Méndez", lastSession: null, nextAppointment: "2026-10-27 10:00", status: "first_attention", category: "academic" },
  { id: 14, enrollment: "1231190", name: "Daniel Mora Vega", program: "Ingenieria Electrica", semester: 9, priority: "high", reason: "Personal o emocional", psychologist: "R. Méndez", lastSession: "2026-10-20 09:00", nextAppointment: "2026-10-24 09:00", status: "referred", category: "condition" },
];