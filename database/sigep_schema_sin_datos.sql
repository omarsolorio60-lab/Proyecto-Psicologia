-- SIGEP: solo estructura, SIN datos personales ni contraseñas.
-- Instalación nueva: phpMyAdmin → pestaña "Importar" → este archivo. Crea la base sigep_db por sí solo.
-- ¿Ya tenías sigep_db con la versión anterior? NO importes este archivo: usa migracion_hu14.sql.
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `sigep_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sigep_db`;

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `psychologist_id` int(11) DEFAULT NULL,
  `requested_date` datetime NOT NULL,
  `appointment_mode` enum('Presencial','Virtual') NOT NULL DEFAULT 'Presencial',
  `student_reason` varchar(255) DEFAULT NULL,
  `confirmed_appointment_date` datetime DEFAULT NULL,
  `priority_level` enum('Normal','Prioritario','Crisis') NOT NULL DEFAULT 'Normal',
  `clinical_reason` varchar(255) DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `status` enum('Solicitada','Confirmada','Atendida','Cancelada','No_Asistio') NOT NULL DEFAULT 'Solicitada',
  `registration_source` enum('Alumno','Psicologa') NOT NULL DEFAULT 'Alumno',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `appointment_slots` (
  `slot_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `psychologists` (
  `psychologist_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `institutional_email` varchar(150) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogo de carreras (HU-14). Confirmar la lista oficial con las clientas.
CREATE TABLE `programs` (
  `program_id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas de acceso (HU-14). Los alumnos entran con Google; password_hash
-- queda listo para el login de administradores/psicólogas (pendiente).
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `google_sub` varchar(64) DEFAULT NULL,
  `role` enum('estudiante','psicologa','practicante','administrador') NOT NULL DEFAULT 'estudiante',
  `password_hash` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Un solo registro por alumno, lo cree quien lo cree:
--   'Cita'         → el alumno pidió cita sin cuenta (frontend/citas)
--   'Autoregistro' → el alumno creó su cuenta con Google (frontend/registro, HU-14)
--   'Psicologa'    → lo capturó la psicóloga (panel)
-- Si un alumno con cuenta reclama una matrícula que ya existía y el correo no
-- coincide, link_needs_review = 1 y lo que escribió se guarda en declared_name:
-- la psicóloga debe confirmar que es la misma persona.
CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_number` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `paternal_last_name` varchar(100) NOT NULL,
  `maternal_last_name` varchar(100) DEFAULT NULL,
  `institutional_email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `sex` enum('M','H','ND') DEFAULT NULL COMMENT 'M = Mujer, H = Hombre, ND = Prefiero no decirlo',
  `program_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `major` varchar(120) DEFAULT NULL COMMENT 'Carrera escrita a mano en el formulario de citas',
  `semester` tinyint(3) UNSIGNED DEFAULT NULL,
  `group_code` varchar(10) DEFAULT NULL,
  `schedule_file` varchar(60) DEFAULT NULL COMMENT 'Archivo en storage/horarios/',
  `schedule_mime` varchar(20) DEFAULT NULL,
  `registration_source` enum('Cita','Autoregistro','Psicologa') NOT NULL DEFAULT 'Cita',
  `link_needs_review` tinyint(1) NOT NULL DEFAULT 0,
  `declared_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `applicants` (
  `applicant_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `high_school` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `career_interest` varchar(120) NOT NULL,
  `privacy_accepted` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `idx_appointments_student` (`student_id`),
  ADD KEY `fk_appointments_psychologists` (`psychologist_id`),
  ADD KEY `idx_appointments_slot` (`requested_date`,`status`,`psychologist_id`);

ALTER TABLE `appointment_slots`
  ADD PRIMARY KEY (`slot_date`);

ALTER TABLE `psychologists`
  ADD PRIMARY KEY (`psychologist_id`),
  ADD UNIQUE KEY `institutional_email` (`institutional_email`);

ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `uq_programs_name` (`name`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_google_sub` (`google_sub`);

ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD UNIQUE KEY `institutional_email` (`institutional_email`),
  ADD UNIQUE KEY `uq_students_user` (`user_id`),
  ADD KEY `fk_students_programs` (`program_id`);

ALTER TABLE `applicants`
  ADD PRIMARY KEY (`applicant_id`);

ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `psychologists`
  MODIFY `psychologist_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `programs`
  MODIFY `program_id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `applicants`
  MODIFY `applicant_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_psychologists` FOREIGN KEY (`psychologist_id`) REFERENCES `psychologists` (`psychologist_id`),
  ADD CONSTRAINT `fk_appointments_students` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON UPDATE CASCADE;

ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_students_programs` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`);

-- Catálogo (no son datos personales).
INSERT IGNORE INTO `programs` (`name`) VALUES
  ('Ingeniería en Software y Tecnologías Emergentes'),
  ('Ingeniería en Mecatrónica'),
  ('Bioingeniería'),
  ('Ingeniería Industrial'),
  ('Ingeniería en Electrónica'),
  ('Tronco común');
