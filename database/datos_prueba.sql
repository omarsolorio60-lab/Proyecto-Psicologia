-- SIGEP: datos de PRUEBA para desarrollo local. No usar en producción.
-- Ejecutar sobre sigep_db después de importar sigep_schema_sin_datos.sql.
-- Se puede ejecutar varias veces: INSERT IGNORE no duplica registros.

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
