-- SIGEP: datos de PRUEBA para desarrollo local. No usar en producción.
-- Ejecutar sobre sigep_db después de importar sigep_schema_sin_datos.sql.
-- Se puede ejecutar varias veces: INSERT IGNORE no duplica registros.
SET NAMES utf8mb4;
USE `sigep_db`;

-- Psicólogas activas (cada una atiende una cita por horario)
INSERT IGNORE INTO psychologists (full_name, institutional_email, active) VALUES
  ('A. López', 'psicologa1.prueba@uabc.edu.mx', 1),
  ('R. Méndez', 'psicologa2.prueba@uabc.edu.mx', 1);

-- Horarios habilitados: lunes a viernes de las próximas 4 semanas,
-- de 08:00 a 16:00 cada 30 minutos (los mismos que muestra frontend/citas).
INSERT IGNORE INTO appointment_slots (slot_date)
WITH RECURSIVE
  days AS (
    SELECT CURDATE() AS d
    UNION ALL
    SELECT d + INTERVAL 1 DAY FROM days WHERE d < CURDATE() + INTERVAL 27 DAY
  ),
  times AS (
    SELECT 0 AS minutes
    UNION ALL
    SELECT minutes + 30 FROM times WHERE minutes < 480
  )
SELECT TIMESTAMP(d, '08:00:00') + INTERVAL minutes MINUTE
FROM days, times
WHERE WEEKDAY(d) < 5;

-- HU-14: alumnos que la psicóloga YA había atendido y que aún no tienen cuenta.
-- Sirven para probar la vinculación (ver README, "Prueba rápida").
--   Juan  → tiene correo guardado: si entra con ese mismo correo se vincula sin revisión.
--   María → sin correo guardado: se vincula, pero queda marcada para que la psicóloga confirme.
INSERT IGNORE INTO students
  (student_number, first_name, paternal_last_name, maternal_last_name, institutional_email,
   phone, sex, program_id, semester, group_code, registration_source)
VALUES
  ('1234567', 'Juan', 'Pérez', 'Martínez', 'juan.perez@uabc.edu.mx', '6861234567', 'H',
   (SELECT program_id FROM programs WHERE name LIKE 'Ingeniería en Software%' LIMIT 1), 5, '551', 'Psicologa'),
  ('2345678', 'María', 'Sánchez', 'Ruiz', NULL, '6869876543', 'M',
   (SELECT program_id FROM programs WHERE name = 'Bioingeniería' LIMIT 1), 3, '331', 'Psicologa');
