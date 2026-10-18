-- =====================================================================
--  EquipRent - Script 03: Consultas clave y pruebas
--  Ejecutar DESPUÉS de 01_schema.sql y 02_seed.sql.
--  Cada bloque indica el resultado ESPERADO para comprobarlo a mano.
-- =====================================================================
USE equiprent;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PRUEBA 1. Disponibilidad de hoy+2 a hoy+6 (todas las categorías)
-- ESPERADO (4 equipos): EQ-1001, EQ-1003, EQ-2001, EQ-3002
--   Quedan fuera:
--   EQ-1002 contrato activo hasta hoy+3     EQ-2002 en mantenimiento
--   EQ-3001 y EQ-6002 contrato aprobado      EQ-4001 fuera de servicio
--   EQ-5001 contrato vencido sin devolver    EQ-6001 contrato pendiente
-- ---------------------------------------------------------------------
CALL sp_equiposDisponibles(DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), NULL);

-- ---------------------------------------------------------------------
-- PRUEBA 2. Disponibilidad de hoy+10 a hoy+12
-- ESPERADO (8 equipos): EQ-1001, EQ-1002, EQ-1003, EQ-2001, EQ-3001,
--   EQ-3002, EQ-6001, EQ-6002
--   (EQ-5001 sigue fuera: está atrasado y no ha vuelto)
-- ---------------------------------------------------------------------
CALL sp_equiposDisponibles(DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), NULL);

-- ---------------------------------------------------------------------
-- PRUEBA 3. Filtro por categoría (1 = Minicargadores), hoy+10 a hoy+12
-- ESPERADO (3 equipos): EQ-1001, EQ-1002, EQ-1003
-- ---------------------------------------------------------------------
CALL sp_equiposDisponibles(DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 1);

-- ---------------------------------------------------------------------
-- PRUEBA 4. Precio vigente: EQ-1001 debe salir a 120.00 (no a 100.00,
-- que es la tarifa de 2025 ya vencida)
-- ESPERADO: precioDiario = 120.00
-- ---------------------------------------------------------------------
SELECT codigoInventario, precioDiario, depositoSugerido
  FROM v_catalogoEquipo WHERE codigoInventario = 'EQ-1001';

-- ---------------------------------------------------------------------
-- PRUEBA 5. Fechas ocupadas de un equipo (para el calendario del cliente)
-- ESPERADO para EQ-6001: un rango de hoy+5 a hoy+9 (Pendiente)
-- ---------------------------------------------------------------------
SELECT c.numeroContrato, c.estado, c.fechaInicio, c.fechaFin
  FROM detalleContrato dc
  JOIN contrato c ON c.idContrato = dc.idContrato
  JOIN equipo e   ON e.idEquipo = dc.idEquipo
 WHERE e.codigoInventario = 'EQ-6001'
   AND c.estado IN ('Pendiente', 'Aprobado', 'Activo')
   AND NOT EXISTS (SELECT 1 FROM detalleDevolucion dd WHERE dd.idDetalleContrato = dc.idDetalleContrato)
 ORDER BY c.fechaInicio;

-- ---------------------------------------------------------------------
-- PRUEBA 6. Indicadores del dashboard (RF-13)
-- ESPERADO: equiposTotales 11, disponibles 7, alquilados 2,
--   enMantenimiento 1, fueraServicio 1, alquileresActivos 2,
--   pendientes 1, atrasados 1
-- ---------------------------------------------------------------------
SELECT
  (SELECT COUNT(*) FROM equipo WHERE activo = TRUE)                                  AS equiposTotales,
  (SELECT COUNT(*) FROM equipo WHERE activo = TRUE AND estado = 'Disponible')        AS disponibles,
  (SELECT COUNT(*) FROM equipo WHERE activo = TRUE AND estado = 'Alquilado')         AS alquilados,
  (SELECT COUNT(*) FROM equipo WHERE activo = TRUE AND estado = 'Mantenimiento')     AS enMantenimiento,
  (SELECT COUNT(*) FROM equipo WHERE activo = TRUE AND estado = 'FueraServicio')     AS fueraServicio,
  (SELECT COUNT(*) FROM contrato WHERE estado = 'Activo')                            AS alquileresActivos,
  (SELECT COUNT(*) FROM contrato WHERE estado = 'Pendiente')                         AS pendientes,
  (SELECT COUNT(*) FROM contrato WHERE estado = 'Activo' AND fechaFin < CURDATE())   AS atrasados;

-- ---------------------------------------------------------------------
-- PRUEBA 7. Bitácora: cada equipo tiene su registro inicial
-- ESPERADO: 11 filas con estadoAnterior NULL
-- ---------------------------------------------------------------------
SELECT COUNT(*) AS registrosIniciales FROM bitacoraEstadoEquipo WHERE estadoAnterior IS NULL;

-- ---------------------------------------------------------------------
-- PRUEBA 8. Cambio de estado registrado en bitácora
-- ESPERADO: aparece una fila Disponible -> FueraServicio y luego se revierte
-- ---------------------------------------------------------------------
SET @app_idUsuario = 1;
SET @app_motivo = 'Prueba de bitácora';
START TRANSACTION;
UPDATE equipo SET estado = 'FueraServicio' WHERE codigoInventario = 'EQ-3002';
SELECT estadoAnterior, estadoNuevo, motivo FROM bitacoraEstadoEquipo ORDER BY idBitacora DESC LIMIT 1;
ROLLBACK;
SET @app_idUsuario = NULL;
SET @app_motivo = NULL;

-- =====================================================================
--  PRUEBAS NEGATIVAS: cada sentencia DEBE FALLAR.
--  Ejecútalas una por una (quita el "-- " del inicio) y confirma el error.
-- =====================================================================

-- N1. Traslape: reservar EQ-1002 (ya activo hasta hoy+3) de hoy+1 a hoy+2
--     ESPERADO: "El equipo ya esta reservado en fechas que se cruzan..."
-- START TRANSACTION;
-- INSERT INTO contrato (fechaInicio, fechaFin, estado, subtotal, depositoGarantia, totalEstimado, idCliente)
--   VALUES (DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Pendiente', 240, 300, 540, 1);
-- INSERT INTO detalleContrato (precioDiarioAplicado, diasFacturados, subtotalLinea, idContrato, idEquipo)
--   VALUES (120, 2, 240, LAST_INSERT_ID(), 2);
-- ROLLBACK;

-- N2. Fechas invertidas en el procedimiento
--     ESPERADO: "Rango de fechas invalido..."
-- CALL sp_equiposDisponibles(DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL);

-- N3. Fecha inicial en el pasado
--     ESPERADO: "La fecha inicial no puede ser anterior a hoy."
-- CALL sp_equiposDisponibles(DATE_SUB(CURDATE(), INTERVAL 1 DAY), CURDATE(), NULL);

-- N4. Total que no cuadra (CHECK ck_contrato_total)
--     ESPERADO: Check constraint 'ck_contrato_total' is violated
-- INSERT INTO contrato (fechaInicio, fechaFin, subtotal, depositoGarantia, totalEstimado, idCliente)
--   VALUES (CURDATE(), CURDATE(), 100, 50, 999, 1);

-- N5. Rechazo sin motivo (CHECK ck_contrato_rechazo)
-- UPDATE contrato SET estado = 'Rechazado' WHERE idContrato = 4;

-- N6. Correo duplicado (UNIQUE uq_usuario_correo)
-- INSERT INTO usuario (nombres, apellidos, correo, claveHash, idRol)
--   VALUES ('X', 'Y', 'admin@equiprent.com', 'x', 1);

-- N7. Devolver dos veces la misma unidad (UNIQUE uq_detalleDevolucion_linea)
-- INSERT INTO detalleDevolucion (estadoFisico, idDevolucion, idDetalleContrato) VALUES ('Bueno', 1, 1);

-- N8. Recibir una unidad que nunca se entregó (contrato 5, línea 5)
--     ESPERADO: "No se puede recibir un equipo que no ha sido entregado."
-- START TRANSACTION;
-- INSERT INTO devolucion (fechaDevolucion, idContrato, idRegistradoPor) VALUES (NOW(), 5, 2);
-- INSERT INTO detalleDevolucion (estadoFisico, idDevolucion, idDetalleContrato) VALUES ('Bueno', LAST_INSERT_ID(), 5);
-- ROLLBACK;

-- N9. Cambiar estado de un equipo sin identificar al usuario
--     ESPERADO: "Falta @app_idUsuario para registrar la bitacora del equipo."
-- UPDATE equipo SET estado = 'Revision' WHERE idEquipo = 1;

-- N10. Dos fotos principales para el mismo equipo (UNIQUE uq_fotografia_principal)
-- INSERT INTO fotografiaEquipo (urlFotografia, esPrincipal, idEquipo) VALUES ('uploads/equipos/x.jpg', TRUE, 1);
