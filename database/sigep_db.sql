-- =============================================================
-- SIGEP: base de datos completa (sigep_db)
-- Incluye: estructura, catalogo de carreras (majors), tabla de
-- casos (cases), psicologas, horarios disponibles y datos de
-- prueba con alumnos inventados.
--
-- Como importar (phpMyAdmin):
--   1. Crear la base sigep_db (utf8mb4_unicode_ci) o seleccionarla.
--   2. Pestana Importar > elegir este archivo > Importar.
-- Se puede volver a importar: borra y crea las tablas de nuevo.
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- -------------------------------------------------------------
-- Estructura
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `cases`;
DROP TABLE IF EXISTS `applicants`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `appointment_slots`;
DROP TABLE IF EXISTS `psychologists`;
DROP TABLE IF EXISTS `majors`;

CREATE TABLE `majors` (
  `major_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`major_id`),
  UNIQUE KEY `uq_majors_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `psychologists` (
  `psychologist_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `institutional_email` varchar(150) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `password_hash` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`psychologist_id`),
  UNIQUE KEY `institutional_email` (`institutional_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `appointment_slots` (
  `slot_date` datetime NOT NULL,
  PRIMARY KEY (`slot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `paternal_last_name` varchar(100) NOT NULL,
  `maternal_last_name` varchar(100) DEFAULT NULL,
  `institutional_email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `major` varchar(120) DEFAULT NULL,
  `major_id` int(11) DEFAULT NULL,
  `semester` tinyint(3) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `student_number` (`student_number`),
  UNIQUE KEY `institutional_email` (`institutional_email`),
  KEY `idx_students_major` (`major_id`),
  CONSTRAINT `fk_students_majors` FOREIGN KEY (`major_id`) REFERENCES `majors` (`major_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `applicants` (
  `applicant_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `high_school` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `career_interest` varchar(120) NOT NULL,
  `major_id` int(11) DEFAULT NULL,
  `privacy_accepted` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`applicant_id`),
  KEY `idx_applicants_major` (`major_id`),
  CONSTRAINT `fk_applicants_majors` FOREIGN KEY (`major_id`) REFERENCES `majors` (`major_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`appointment_id`),
  KEY `idx_appointments_student` (`student_id`),
  KEY `fk_appointments_psychologists` (`psychologist_id`),
  KEY `idx_appointments_slot` (`requested_date`,`status`,`psychologist_id`),
  CONSTRAINT `fk_appointments_psychologists` FOREIGN KEY (`psychologist_id`) REFERENCES `psychologists` (`psychologist_id`),
  CONSTRAINT `fk_appointments_students` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cases` (
  `case_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `psychologist_id` int(11) DEFAULT NULL,
  `status` enum('first_attention','follow_up','referred','closed') NOT NULL DEFAULT 'first_attention',
  `priority` enum('high','medium','stable') NOT NULL DEFAULT 'stable',
  `category` enum('academic','emotional','condition') NOT NULL DEFAULT 'academic',
  `reason` enum('academic','personal','vocational','excuse') NOT NULL DEFAULT 'academic',
  `opened_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`case_id`),
  KEY `idx_cases_student` (`student_id`),
  KEY `idx_cases_psychologist_status` (`psychologist_id`,`status`),
  CONSTRAINT `fk_cases_psychologists` FOREIGN KEY (`psychologist_id`) REFERENCES `psychologists` (`psychologist_id`),
  CONSTRAINT `fk_cases_students` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- Catalogos y datos base
-- -------------------------------------------------------------

INSERT INTO `majors` (`major_id`, `name`, `active`) VALUES (1,'Bioingenieria',1),
(2,'Ingenieria en Software y Tecnologias Emergentes',1),
(3,'Ingenieria Aeroespacial',1),
(4,'Ingenieria Civil',1),
(5,'Ingenieria Electrica',1),
(6,'Ingenieria en Energias Renovables',1),
(7,'Ingenieria Industrial',1),
(8,'Ingenieria Mecanica',1),
(9,'Ingenieria Mecatronica',1),
(10,'Ingenieria en Semiconductores y Microelectronica',1),
(11,'Arquitectura',1),
(12,'Diseño Grafico',1),
(13,'Diseño Industrial',1);

-- Contrasena de prueba de las dos psicologas: Psicologa2026
-- (se guarda como hash con password_hash de PHP, nunca en texto plano)
INSERT INTO `psychologists` (`psychologist_id`, `full_name`, `institutional_email`, `active`, `password_hash`) VALUES
(3,'A. López','psicologa1.prueba@uabc.edu.mx',1,'$2y$10$hVy6oHnNc6kI0VSQV4XRn.IDCJM81nPz9z0IfhKyVbk7PWBF0hSEy'),
(4,'R. Méndez','psicologa2.prueba@uabc.edu.mx',1,'$2y$10$S5CHJ33FwNBGZNph2pruquZn4ePmxuXXuJv6qSDR5F.6IpVLYXGCa');

-- Horarios de atencion: lunes a viernes a las 09:00, 11:00 y 13:00
-- (una cita por psicologa en cada horario). Periodo 2026-2.
INSERT INTO `appointment_slots` (`slot_date`) VALUES
('2026-10-26 09:00:00'),
('2026-10-26 11:00:00'),
('2026-10-26 13:00:00'),
('2026-10-27 09:00:00'),
('2026-10-27 11:00:00'),
('2026-10-27 13:00:00'),
('2026-10-28 09:00:00'),
('2026-10-28 11:00:00'),
('2026-10-28 13:00:00'),
('2026-10-29 09:00:00'),
('2026-10-29 11:00:00'),
('2026-10-29 13:00:00'),
('2026-10-30 09:00:00'),
('2026-10-30 11:00:00'),
('2026-10-30 13:00:00'),
('2026-11-02 09:00:00'),
('2026-11-02 11:00:00'),
('2026-11-02 13:00:00'),
('2026-11-03 09:00:00'),
('2026-11-03 11:00:00'),
('2026-11-03 13:00:00'),
('2026-11-04 09:00:00'),
('2026-11-04 11:00:00'),
('2026-11-04 13:00:00'),
('2026-11-05 09:00:00'),
('2026-11-05 11:00:00'),
('2026-11-05 13:00:00'),
('2026-11-06 09:00:00'),
('2026-11-06 11:00:00'),
('2026-11-06 13:00:00'),
('2026-11-09 09:00:00'),
('2026-11-09 11:00:00'),
('2026-11-09 13:00:00'),
('2026-11-10 09:00:00'),
('2026-11-10 11:00:00'),
('2026-11-10 13:00:00'),
('2026-11-11 09:00:00'),
('2026-11-11 11:00:00'),
('2026-11-11 13:00:00'),
('2026-11-12 09:00:00'),
('2026-11-12 11:00:00'),
('2026-11-12 13:00:00'),
('2026-11-13 09:00:00'),
('2026-11-13 11:00:00'),
('2026-11-13 13:00:00'),
('2026-11-16 09:00:00'),
('2026-11-16 11:00:00'),
('2026-11-16 13:00:00'),
('2026-11-17 09:00:00'),
('2026-11-17 11:00:00'),
('2026-11-17 13:00:00'),
('2026-11-18 09:00:00'),
('2026-11-18 11:00:00'),
('2026-11-18 13:00:00'),
('2026-11-19 09:00:00'),
('2026-11-19 11:00:00'),
('2026-11-19 13:00:00'),
('2026-11-20 09:00:00'),
('2026-11-20 11:00:00'),
('2026-11-20 13:00:00'),
('2026-11-23 09:00:00'),
('2026-11-23 11:00:00'),
('2026-11-23 13:00:00'),
('2026-11-24 09:00:00'),
('2026-11-24 11:00:00'),
('2026-11-24 13:00:00'),
('2026-11-25 09:00:00'),
('2026-11-25 11:00:00'),
('2026-11-25 13:00:00'),
('2026-11-26 09:00:00'),
('2026-11-26 11:00:00'),
('2026-11-26 13:00:00'),
('2026-11-27 09:00:00'),
('2026-11-27 11:00:00'),
('2026-11-27 13:00:00'),
('2026-11-30 09:00:00'),
('2026-11-30 11:00:00'),
('2026-11-30 13:00:00'),
('2026-12-01 09:00:00'),
('2026-12-01 11:00:00'),
('2026-12-01 13:00:00'),
('2026-12-02 09:00:00'),
('2026-12-02 11:00:00'),
('2026-12-02 13:00:00'),
('2026-12-03 09:00:00'),
('2026-12-03 11:00:00'),
('2026-12-03 13:00:00'),
('2026-12-04 09:00:00'),
('2026-12-04 11:00:00'),
('2026-12-04 13:00:00'),
('2026-12-07 09:00:00'),
('2026-12-07 11:00:00'),
('2026-12-07 13:00:00'),
('2026-12-08 09:00:00'),
('2026-12-08 11:00:00'),
('2026-12-08 13:00:00'),
('2026-12-09 09:00:00'),
('2026-12-09 11:00:00'),
('2026-12-09 13:00:00'),
('2026-12-10 09:00:00'),
('2026-12-10 11:00:00'),
('2026-12-10 13:00:00'),
('2026-12-11 09:00:00'),
('2026-12-11 11:00:00'),
('2026-12-11 13:00:00');



-- -------------------------------------------------------------
-- Datos de prueba (alumnos inventados, citas con fecha de hoy)
-- Todas las citas usan los horarios 09:00, 11:00 o 13:00
-- -------------------------------------------------------------
START TRANSACTION;

INSERT INTO `students` (`student_number`, `first_name`, `paternal_last_name`, `maternal_last_name`, `institutional_email`, `phone`, `major`, `major_id`, `semester`) VALUES
('1270001', 'Juan', 'Perez', 'Martinez', 'prueba.juan@uabc.edu.mx', '6640000001', 'Ingenieria en Software y Tecnologias Emergentes', 2, 5),
('1270002', 'Maria', 'Sanchez', 'Ruiz', 'prueba.maria@uabc.edu.mx', '6640000002', 'Bioingenieria', 1, 3),
('1270003', 'Luis', 'Ortega', 'Diaz', 'prueba.luis@uabc.edu.mx', '6640000003', 'Ingenieria en Software y Tecnologias Emergentes', 2, 5),
('1270004', 'Ana', 'Castro', 'Vega', 'prueba.ana@uabc.edu.mx', '6640000004', 'Ingenieria Mecatronica', 9, 3),
('1270005', 'Natalia', 'Bravo', 'Cruz', 'prueba.natalia@uabc.edu.mx', '6640000005', 'Ingenieria Electrica', 5, 5),
('1270006', 'Ricardo', 'Gomez', 'Leon', 'prueba.ricardo@uabc.edu.mx', '6640000006', 'Ingenieria Industrial', 7, 7),
('1270007', 'Fernanda', 'Diaz', 'Hernandez', 'prueba.fernanda@uabc.edu.mx', '6640000007', 'Bioingenieria', 1, 5),
('1270008', 'Diego', 'Flores', 'Mora', 'prueba.diego@uabc.edu.mx', '6640000008', 'Arquitectura', 11, 7);

-- Psicologa 3 = A. Lopez, psicologa 4 = R. Mendez (tabla psychologists)
INSERT INTO `cases` (`student_id`, `psychologist_id`, `status`, `priority`, `category`, `reason`)
SELECT student_id, 3, 'follow_up', 'high', 'academic', 'academic' FROM students WHERE student_number = '1270001'
UNION ALL SELECT student_id, 3, 'follow_up', 'medium', 'academic', 'academic' FROM students WHERE student_number = '1270002'
UNION ALL SELECT student_id, 3, 'first_attention', 'medium', 'academic', 'academic' FROM students WHERE student_number = '1270003'
UNION ALL SELECT student_id, 3, 'referred', 'high', 'condition', 'personal' FROM students WHERE student_number = '1270004'
UNION ALL SELECT student_id, 3, 'follow_up', 'medium', 'academic', 'vocational' FROM students WHERE student_number = '1270005'
UNION ALL SELECT student_id, 3, 'follow_up', 'high', 'condition', 'personal' FROM students WHERE student_number = '1270006'
UNION ALL SELECT student_id, 4, 'follow_up', 'medium', 'emotional', 'personal' FROM students WHERE student_number = '1270007'
UNION ALL SELECT student_id, 3, 'closed', 'stable', 'academic', 'academic' FROM students WHERE student_number = '1270008';

-- Citas de hoy (se calculan con CURDATE) y sesiones pasadas ya atendidas
INSERT INTO `appointments` (`student_id`, `psychologist_id`, `requested_date`, `confirmed_appointment_date`, `appointment_mode`, `student_reason`, `priority_level`, `status`, `registration_source`)
SELECT student_id, 3, CURDATE() + INTERVAL 9 HOUR, CURDATE() + INTERVAL 9 HOUR, 'Presencial', 'Bajas calificaciones', 'Crisis', 'Confirmada', 'Psicologa' FROM students WHERE student_number = '1270001'
UNION ALL SELECT student_id, 3, CURDATE() + INTERVAL 11 HOUR, CURDATE() + INTERVAL 11 HOUR, 'Virtual', 'Seguimiento academico', 'Prioritario', 'Confirmada', 'Psicologa' FROM students WHERE student_number = '1270002'
UNION ALL SELECT student_id, 3, CURDATE() + INTERVAL 1 DAY + INTERVAL 9 HOUR, NULL, 'Virtual', 'Dudas de carga academica', 'Normal', 'Solicitada', 'Alumno' FROM students WHERE student_number = '1270003'
UNION ALL SELECT student_id, 3, CURDATE() + INTERVAL 13 HOUR, CURDATE() + INTERVAL 13 HOUR, 'Presencial', 'Situacion personal', 'Crisis', 'Confirmada', 'Psicologa' FROM students WHERE student_number = '1270004'
UNION ALL SELECT student_id, 4, CURDATE() + INTERVAL 11 HOUR, CURDATE() + INTERVAL 11 HOUR, 'Presencial', 'Situacion personal', 'Prioritario', 'Confirmada', 'Psicologa' FROM students WHERE student_number = '1270007'
UNION ALL SELECT student_id, 3, CURDATE() - INTERVAL 13 DAY + INTERVAL 9 HOUR, CURDATE() - INTERVAL 13 DAY + INTERVAL 9 HOUR, 'Presencial', 'Primera entrevista', 'Normal', 'Atendida', 'Psicologa' FROM students WHERE student_number = '1270001'
UNION ALL SELECT student_id, 3, CURDATE() - INTERVAL 10 DAY + INTERVAL 11 HOUR, CURDATE() - INTERVAL 10 DAY + INTERVAL 11 HOUR, 'Virtual', 'Primera entrevista', 'Normal', 'Atendida', 'Psicologa' FROM students WHERE student_number = '1270002'
UNION ALL SELECT student_id, 3, CURDATE() - INTERVAL 14 DAY + INTERVAL 11 HOUR, CURDATE() - INTERVAL 14 DAY + INTERVAL 11 HOUR, 'Presencial', 'Primera entrevista', 'Normal', 'Atendida', 'Psicologa' FROM students WHERE student_number = '1270006'
UNION ALL SELECT student_id, 3, CURDATE() + INTERVAL 3 DAY + INTERVAL 13 HOUR, CURDATE() + INTERVAL 3 DAY + INTERVAL 13 HOUR, 'Presencial', 'Seguimiento', 'Normal', 'Confirmada', 'Psicologa' FROM students WHERE student_number = '1270006';

COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
