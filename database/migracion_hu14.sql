-- SIGEP · Migración para bases sigep_db que YA EXISTÍAN (versión anterior a la HU-14).
-- Agrega las tablas users y programs y las columnas nuevas de students SIN borrar datos.
-- phpMyAdmin → selecciona sigep_db → pestaña "Importar" → este archivo.
-- Ejecútalo UNA sola vez. Si es una instalación nueva, usa sigep_schema_sin_datos.sql.
SET NAMES utf8mb4;
USE `sigep_db`;

CREATE TABLE IF NOT EXISTS `programs` (
  `program_id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`program_id`),
  UNIQUE KEY `uq_programs_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `google_sub` varchar(64) DEFAULT NULL,
  `role` enum('estudiante','psicologa','practicante','administrador') NOT NULL DEFAULT 'estudiante',
  `password_hash` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_google_sub` (`google_sub`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `students`
  MODIFY `institutional_email` varchar(150) DEFAULT NULL,
  ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `student_id`,
  ADD COLUMN `sex` enum('M','H','ND') DEFAULT NULL COMMENT 'M = Mujer, H = Hombre, ND = Prefiero no decirlo' AFTER `phone`,
  ADD COLUMN `program_id` tinyint(3) UNSIGNED DEFAULT NULL AFTER `sex`,
  ADD COLUMN `group_code` varchar(10) DEFAULT NULL AFTER `semester`,
  ADD COLUMN `schedule_file` varchar(60) DEFAULT NULL COMMENT 'Archivo en storage/horarios/' AFTER `group_code`,
  ADD COLUMN `schedule_mime` varchar(20) DEFAULT NULL AFTER `schedule_file`,
  ADD COLUMN `registration_source` enum('Cita','Autoregistro','Psicologa') NOT NULL DEFAULT 'Cita' AFTER `schedule_mime`,
  ADD COLUMN `link_needs_review` tinyint(1) NOT NULL DEFAULT 0 AFTER `registration_source`,
  ADD COLUMN `declared_name` varchar(255) DEFAULT NULL AFTER `link_needs_review`,
  ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;

ALTER TABLE `students`
  ADD UNIQUE KEY `uq_students_user` (`user_id`),
  ADD KEY `fk_students_programs` (`program_id`),
  ADD CONSTRAINT `fk_students_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_students_programs` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`);

INSERT IGNORE INTO `programs` (`name`) VALUES
  ('Ingeniería en Software y Tecnologías Emergentes'),
  ('Ingeniería en Mecatrónica'),
  ('Bioingeniería'),
  ('Ingeniería Industrial'),
  ('Ingeniería en Electrónica'),
  ('Tronco común');
