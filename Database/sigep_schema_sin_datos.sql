-- SIGEP: solo estructura, SIN datos personales ni contraseñas.
-- Crear primero una base de datos llamada sigep_db y seleccionarla en phpMyAdmin.
SET NAMES utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `psychologists` (
  `psychologist_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `institutional_email` varchar(150) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `student_number` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `paternal_last_name` varchar(100) NOT NULL,
  `maternal_last_name` varchar(100) DEFAULT NULL,
  `institutional_email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `major` varchar(120) DEFAULT NULL,
  `semester` tinyint(3) UNSIGNED DEFAULT NULL,
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

ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD UNIQUE KEY `institutional_email` (`institutional_email`);

ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

ALTER TABLE `psychologists`
  MODIFY `psychologist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_psychologists` FOREIGN KEY (`psychologist_id`) REFERENCES `psychologists` (`psychologist_id`),
  ADD CONSTRAINT `fk_appointments_students` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON UPDATE CASCADE;
