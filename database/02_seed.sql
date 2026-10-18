-- =====================================================================
--  EquipRent - Script 02: Datos de prueba
--  Ejecutar DESPUÉS de 01_schema.sql
--
--  Las fechas se calculan desde el día en que se ejecuta el script
--  (CURDATE()), así los escenarios de prueba siempre tienen sentido.
--
--  Contraseña de TODOS los usuarios de prueba:  Equip2026!
-- =====================================================================
USE equiprent;
SET NAMES utf8mb4;

-- Usuario que firma la bitácora de los equipos durante la carga
SET @app_idUsuario = 1;
SET @app_motivo = 'Carga inicial de datos de prueba';

START TRANSACTION;

-- ---------------------------------------------------------------------
-- Roles
-- ---------------------------------------------------------------------
INSERT INTO rol (idRol, nombre, descripcion) VALUES
 (1, 'Administrador', 'Control total: usuarios, inventario, tarifas, contratos y reportes'),
 (2, 'Operador',      'Operación diaria: solicitudes, entregas, devoluciones, daños y mantenimiento'),
 (3, 'Cliente',       'Consulta del catálogo y gestión de sus propios alquileres');

-- ---------------------------------------------------------------------
-- Usuarios (hash bcrypt de "Equip2026!")
-- ---------------------------------------------------------------------
INSERT INTO usuario (idUsuario, nombres, apellidos, correo, claveHash, telefono, documentoIdentidad, direccion, idRol) VALUES
 (1, 'Administrador', 'General',   'admin@equiprent.com',      '$2y$10$2CzEwL7atnVYsqrlICDLWeN9/LhGSBtn0n29fZaUOd3SBF1nTdl6O', '2222-0001', '00000001-1', 'Oficinas centrales, San Salvador', 1),
 (2, 'William',       'Martínez',  'william@equiprent.com',    '$2y$10$2CzEwL7atnVYsqrlICDLWeN9/LhGSBtn0n29fZaUOd3SBF1nTdl6O', '7000-0002', '00000002-2', NULL, 2),
 (3, 'Ana',           'Gómez',     'ana@equiprent.com',        '$2y$10$2CzEwL7atnVYsqrlICDLWeN9/LhGSBtn0n29fZaUOd3SBF1nTdl6O', '7000-0003', '00000003-3', NULL, 2),
 (4, 'Juan',          'Pérez',     'contacto@constructora.com','$2y$10$2CzEwL7atnVYsqrlICDLWeN9/LhGSBtn0n29fZaUOd3SBF1nTdl6O', '2250-1000', '01234567-8', 'Colonia Escalón, San Salvador', 3),
 (5, 'Carlos',        'Mendoza',   'carlos.mendoza@correo.com','$2y$10$2CzEwL7atnVYsqrlICDLWeN9/LhGSBtn0n29fZaUOd3SBF1nTdl6O', '7111-2222', '02345678-9', 'Santa Tecla, La Libertad', 3),
 (6, 'María',         'López',     'compras@delvalle.com',     '$2y$10$2CzEwL7atnVYsqrlICDLWeN9/LhGSBtn0n29fZaUOd3SBF1nTdl6O', '2440-3000', '03456789-0', 'Santa Ana, Santa Ana', 3);

-- ---------------------------------------------------------------------
-- Clientes (1:1 con usuarios de rol Cliente)
-- ---------------------------------------------------------------------
INSERT INTO cliente (idCliente, codigoCliente, tipoPersona, documentoTributario, razonSocial, contactoAlterno, telefonoAlterno, idUsuario) VALUES
 (1, 'CLI-000001', 'Juridica', '0614-150820-102-1', 'Constructora SA de CV',              'Pedro Ramírez', '7222-3333', 4),
 (2, 'CLI-000002', 'Natural',  NULL,                 NULL,                                 NULL,            NULL,        5),
 (3, 'CLI-000003', 'Juridica', '0210-010119-101-5', 'Constructora del Valle S.A. de C.V.', 'Luis Herrera',  '7333-4444', 6);

-- ---------------------------------------------------------------------
-- Categorías
-- ---------------------------------------------------------------------
INSERT INTO categoria (idCategoria, nombre, descripcion) VALUES
 (1, 'Minicargadores',       'Maquinaria ligera de carga y pala frontal'),
 (2, 'Excavadoras',          'Maquinaria pesada para movimiento de tierra'),
 (3, 'Martillos Hidráulicos','Accesorios para demolición y fractura'),
 (4, 'Compactadores',        'Equipos para compactación de suelos y asfalto'),
 (5, 'Generadores Diésel',   'Plantas auxiliares de generación eléctrica'),
 (6, 'Retroexcavadoras',     'Maquinaria mixta de excavación y carga');

-- ---------------------------------------------------------------------
-- Equipos (cada fila es una unidad física)
-- ---------------------------------------------------------------------
INSERT INTO equipo (idEquipo, codigoInventario, nombre, marca, modelo, numeroSerie, descripcion, caracteristicasTecnicas, condicionesUso, estado, fechaAdquisicion, valorReferencia, idCategoria) VALUES
 (1,  'EQ-1001', 'Minicargador Bobcat S70',        'Bobcat',      'S70',   'BOB-S70-0001',  'Minicargador compacto para espacios reducidos.', 'Motor: Kubota D1005 (23.5 HP); Capacidad operativa: 340 kg; Combustible: Diésel', 'Operador con experiencia. No apto para pendientes mayores a 20%.', 'Disponible',    '2024-03-15', 28000.00, 1),
 (2,  'EQ-1002', 'Minicargador Bobcat S70',        'Bobcat',      'S70',   'BOB-S70-0002',  'Minicargador compacto para espacios reducidos.', 'Motor: Kubota D1005 (23.5 HP); Capacidad operativa: 340 kg; Combustible: Diésel', 'Operador con experiencia. No apto para pendientes mayores a 20%.', 'Alquilado',     '2024-03-15', 28000.00, 1),
 (3,  'EQ-1003', 'Minicargador Bobcat S18',        'Bobcat',      'S18',   'BOB-S18-0003',  'Minicargador de uso general.',                  'Motor: 49 HP; Capacidad operativa: 860 kg; Combustible: Diésel',                    'Operador con experiencia.', 'Disponible',    '2023-11-02', 35000.00, 1),
 (4,  'EQ-2001', 'Excavadora Caterpillar 320D',    'Caterpillar', '320D',  'CAT-320D-0001', 'Excavadora hidráulica de orugas.',              'Peso operativo: 20 t; Potencia: 148 HP; Profundidad máx.: 6.7 m',                   'Requiere operador certificado y transporte en lowboy.', 'Disponible', '2023-08-22', 145000.00, 2),
 (5,  'EQ-2002', 'Excavadora Caterpillar 320D',    'Caterpillar', '320D',  'CAT-320D-0002', 'Excavadora hidráulica de orugas.',              'Peso operativo: 20 t; Potencia: 148 HP; Profundidad máx.: 6.7 m',                   'Requiere operador certificado y transporte en lowboy.', 'Mantenimiento', '2023-08-22', 145000.00, 2),
 (6,  'EQ-3001', 'Martillo Hidráulico HB10G',      'Furukawa',    'HB10G', 'FUR-HB10G-0001','Martillo para demolición montado en excavadora.','Peso: 1,100 kg; Frecuencia: 350-650 golpes/min',                                    'Compatible con excavadoras de 12 a 20 t.', 'Disponible', '2025-01-10', 18000.00, 3),
 (7,  'EQ-3002', 'Martillo Hidráulico HB10G',      'Furukawa',    'HB10G', 'FUR-HB10G-0002','Martillo para demolición montado en excavadora.','Peso: 1,100 kg; Frecuencia: 350-650 golpes/min',                                    'Compatible con excavadoras de 12 a 20 t.', 'Disponible', '2025-01-10', 18000.00, 3),
 (8,  'EQ-4001', 'Compactadora Dynapac CA250',     'Dynapac',     'CA250', 'DYN-CA250-0001','Rodo compactador vibratorio.',                  'Peso: 10 t; Ancho de tambor: 2.13 m',                                               'Uso en suelos granulares.', 'FueraServicio', '2022-11-05', 60000.00, 4),
 (9,  'EQ-5001', 'Generador Diésel CAT 50kVA',     'Caterpillar', 'DE50',  'CAT-DE50-0001', 'Planta eléctrica insonorizada.',                'Potencia: 50 kVA; Tanque: 120 L; Autonomía: 12 h',                                  'Entregar con tanque lleno.', 'Alquilado', '2024-06-01', 15000.00, 5),
 (10, 'EQ-6001', 'Retroexcavadora CAT 416F',       'Caterpillar', '416F',  'CAT-416F-0001', 'Retroexcavadora 4x4.',                          'Potencia: 87 HP; Profundidad máx.: 4.3 m',                                          'Requiere operador certificado.', 'Disponible', '2024-02-20', 75000.00, 6),
 (11, 'EQ-6002', 'Retroexcavadora JCB 3CX',        'JCB',         '3CX',   'JCB-3CX-0001',  'Retroexcavadora 4x4.',                          'Potencia: 74 kW; Profundidad máx.: 5.9 m',                                          'Requiere operador certificado.', 'Disponible', '2024-07-11', 72000.00, 6);

-- ---------------------------------------------------------------------
-- Fotografías (rutas relativas a public/uploads)
-- ---------------------------------------------------------------------
INSERT INTO fotografiaEquipo (urlFotografia, textoAlternativo, ordenVisualizacion, esPrincipal, idEquipo) VALUES
 ('uploads/equipos/eq-1001.jpg', 'Minicargador Bobcat S70 vista frontal', 1, TRUE, 1),
 ('uploads/equipos/eq-1002.jpg', 'Minicargador Bobcat S70 vista frontal', 1, TRUE, 2),
 ('uploads/equipos/eq-1003.jpg', 'Minicargador Bobcat S18',               1, TRUE, 3),
 ('uploads/equipos/eq-2001.jpg', 'Excavadora Caterpillar 320D',           1, TRUE, 4),
 ('uploads/equipos/eq-2001-b.jpg','Excavadora Caterpillar 320D lateral',  2, FALSE, 4),
 ('uploads/equipos/eq-6001.jpg', 'Retroexcavadora CAT 416F',              1, TRUE, 10);

-- ---------------------------------------------------------------------
-- Tarifas diarias vigentes
-- ---------------------------------------------------------------------
INSERT INTO tarifa (tipoPeriodo, monto, depositoSugerido, fechaInicioVigencia, idEquipo) VALUES
 ('Dia', 120.00,  300.00, '2026-01-01', 1),
 ('Dia', 120.00,  300.00, '2026-01-01', 2),
 ('Dia', 110.00,  300.00, '2026-01-01', 3),
 ('Dia', 450.00, 1500.00, '2026-01-01', 4),
 ('Dia', 450.00, 1500.00, '2026-01-01', 5),
 ('Dia',  80.00,  200.00, '2026-01-01', 6),
 ('Dia',  80.00,  200.00, '2026-01-01', 7),
 ('Dia', 150.00,  400.00, '2026-01-01', 8),
 ('Dia',  90.00,  250.00, '2026-01-01', 9),
 ('Dia', 280.00,  800.00, '2026-01-01', 10),
 ('Dia', 260.00,  800.00, '2026-01-01', 11);

-- Tarifa histórica (ya vencida) para comprobar que la vista toma la vigente
INSERT INTO tarifa (tipoPeriodo, monto, depositoSugerido, fechaInicioVigencia, fechaFinVigencia, activa, idEquipo) VALUES
 ('Dia', 100.00, 250.00, '2025-01-01', '2025-12-31', TRUE, 1);

-- ---------------------------------------------------------------------
-- Contratos
--   1  Finalizado  Excavadora 320D       hace 30 a 25 días (con daño leve)
--   2  Activo      Bobcat S70 #2         hace 2 días a dentro de 3 días
--   3  Activo      Generador 50kVA       vencido hace 3 días (ATRASADO)
--   4  Pendiente   Retro CAT 416F        dentro de 5 a 9 días
--   5  Aprobado    JCB 3CX + Martillo    dentro de 1 a 4 días
--   6  Rechazado   Excavadora 320D       dentro de 5 a 7 días
-- ---------------------------------------------------------------------
SET @hoy = CURDATE();

-- Contrato 1: se crea Activo, se entrega, se devuelve y luego se finaliza
INSERT INTO contrato (idContrato, numeroContrato, fechaSolicitud, fechaInicio, fechaFin, destino, estado, subtotal, depositoGarantia, totalEstimado, fechaAprobacion, idCliente, idGestionadoPor) VALUES
 (1, 'ALQ-2026-00001', TIMESTAMP(DATE_SUB(@hoy, INTERVAL 33 DAY), '10:00:00'), DATE_SUB(@hoy, INTERVAL 30 DAY), DATE_SUB(@hoy, INTERVAL 25 DAY),
  'Proyecto Residencial San Lucas, Antiguo Cuscatlán', 'Activo', 2700.00, 1500.00, 4200.00, TIMESTAMP(DATE_SUB(@hoy, INTERVAL 32 DAY), '09:00:00'), 1, 2);
INSERT INTO detalleContrato (idDetalleContrato, precioDiarioAplicado, diasFacturados, subtotalLinea, idContrato, idEquipo) VALUES
 (1, 450.00, 6, 2700.00, 1, 4);

INSERT INTO contrato (idContrato, numeroContrato, fechaSolicitud, fechaInicio, fechaFin, destino, estado, subtotal, depositoGarantia, totalEstimado, fechaAprobacion, idCliente, idGestionadoPor) VALUES
 (2, 'ALQ-2026-00002', TIMESTAMP(DATE_SUB(@hoy, INTERVAL 5 DAY), '11:30:00'), DATE_SUB(@hoy, INTERVAL 2 DAY), DATE_ADD(@hoy, INTERVAL 3 DAY),
  'Edificio Los Orquídeas, Santa Tecla', 'Activo', 720.00, 300.00, 1020.00, TIMESTAMP(DATE_SUB(@hoy, INTERVAL 4 DAY), '08:15:00'), 2, 2);
INSERT INTO detalleContrato (idDetalleContrato, precioDiarioAplicado, diasFacturados, subtotalLinea, idContrato, idEquipo) VALUES
 (2, 120.00, 6, 720.00, 2, 2);

INSERT INTO contrato (idContrato, numeroContrato, fechaSolicitud, fechaInicio, fechaFin, destino, estado, subtotal, depositoGarantia, totalEstimado, fechaAprobacion, idCliente, idGestionadoPor) VALUES
 (3, 'ALQ-2026-00003', TIMESTAMP(DATE_SUB(@hoy, INTERVAL 12 DAY), '14:00:00'), DATE_SUB(@hoy, INTERVAL 10 DAY), DATE_SUB(@hoy, INTERVAL 3 DAY),
  'Canal de Riego Sur, Santa Ana', 'Activo', 720.00, 250.00, 970.00, TIMESTAMP(DATE_SUB(@hoy, INTERVAL 11 DAY), '09:00:00'), 3, 3);
INSERT INTO detalleContrato (idDetalleContrato, precioDiarioAplicado, diasFacturados, subtotalLinea, idContrato, idEquipo) VALUES
 (3, 90.00, 8, 720.00, 3, 9);

INSERT INTO contrato (idContrato, numeroContrato, fechaSolicitud, fechaInicio, fechaFin, destino, estado, subtotal, depositoGarantia, totalEstimado, idCliente) VALUES
 (4, 'ALQ-2026-00004', TIMESTAMP(@hoy, '08:45:00'), DATE_ADD(@hoy, INTERVAL 5 DAY), DATE_ADD(@hoy, INTERVAL 9 DAY),
  'Pavimentación Ruta 4, Sonsonate', 'Pendiente', 1400.00, 800.00, 2200.00, 1);
INSERT INTO detalleContrato (idDetalleContrato, precioDiarioAplicado, diasFacturados, subtotalLinea, idContrato, idEquipo) VALUES
 (4, 280.00, 5, 1400.00, 4, 10);

INSERT INTO contrato (idContrato, numeroContrato, fechaSolicitud, fechaInicio, fechaFin, destino, estado, subtotal, depositoGarantia, totalEstimado, fechaAprobacion, idCliente, idGestionadoPor) VALUES
 (5, 'ALQ-2026-00005', TIMESTAMP(DATE_SUB(@hoy, INTERVAL 1 DAY), '16:20:00'), DATE_ADD(@hoy, INTERVAL 1 DAY), DATE_ADD(@hoy, INTERVAL 4 DAY),
  'Demolición bodega antigua, Soyapango', 'Aprobado', 1360.00, 1000.00, 2360.00, TIMESTAMP(@hoy, '07:50:00'), 2, 2);
INSERT INTO detalleContrato (idDetalleContrato, precioDiarioAplicado, diasFacturados, subtotalLinea, idContrato, idEquipo) VALUES
 (5, 260.00, 4, 1040.00, 5, 11),
 (6,  80.00, 4,  320.00, 5, 6);

INSERT INTO contrato (idContrato, numeroContrato, fechaSolicitud, fechaInicio, fechaFin, destino, estado, subtotal, depositoGarantia, totalEstimado, motivoRechazo, idCliente, idGestionadoPor) VALUES
 (6, 'ALQ-2026-00006', TIMESTAMP(DATE_SUB(@hoy, INTERVAL 2 DAY), '13:10:00'), DATE_ADD(@hoy, INTERVAL 5 DAY), DATE_ADD(@hoy, INTERVAL 7 DAY),
  'Terracería lote 12, La Libertad', 'Rechazado', 1350.00, 1500.00, 2850.00, 'Documentación tributaria incompleta.', 3, 3);
INSERT INTO detalleContrato (idDetalleContrato, precioDiarioAplicado, diasFacturados, subtotalLinea, idContrato, idEquipo) VALUES
 (7, 450.00, 3, 1350.00, 6, 4);

-- ---------------------------------------------------------------------
-- Entregas (contratos 1, 2 y 3)
-- ---------------------------------------------------------------------
INSERT INTO entrega (idEntrega, fechaEntrega, nombreReceptor, documentoReceptor, observaciones, idContrato, idRegistradoPor) VALUES
 (1, TIMESTAMP(DATE_SUB(@hoy, INTERVAL 30 DAY), '08:00:00'), 'Pedro Ramírez',  '04567890-1', 'Entrega en obra con lowboy.', 1, 2),
 (2, TIMESTAMP(DATE_SUB(@hoy, INTERVAL 2 DAY),  '07:30:00'), 'Carlos Mendoza', '02345678-9', NULL,                          2, 2),
 (3, TIMESTAMP(DATE_SUB(@hoy, INTERVAL 10 DAY), '09:15:00'), 'Luis Herrera',   '05678901-2', 'Se entregó con tanque lleno.',3, 3);

INSERT INTO detalleEntrega (estadoFisico, nivelCombustible, lecturaHorometro, accesoriosEntregados, idEntrega, idDetalleContrato) VALUES
 ('Óptimo (100% operativo)', 100.00, 3520.50, 'Cucharón 36", llaves, extintor', 1, 1),
 ('Bueno (desgaste menor)',   90.00, 1210.00, 'Cucharón estándar, llaves',      2, 2),
 ('Óptimo (100% operativo)', 100.00,  845.30, 'Cables de conexión, llaves',     3, 3);

-- ---------------------------------------------------------------------
-- Devolución del contrato 1 (con daño leve) y cierre del contrato
-- ---------------------------------------------------------------------
INSERT INTO devolucion (idDevolucion, fechaDevolucion, diasAtraso, penalizacionTotal, observaciones, idContrato, idRegistradoPor) VALUES
 (1, TIMESTAMP(DATE_SUB(@hoy, INTERVAL 25 DAY), '16:40:00'), 0, 0.00, 'Devolución en fecha.', 1, 2);

INSERT INTO detalleDevolucion (idDetalleDevolucion, estadoFisico, nivelCombustible, lecturaHorometro, accesoriosRecibidos, penalizacion, observaciones, idDevolucion, idDetalleContrato) VALUES
 (1, 'Bueno (desgaste menor)', 35.00, 3568.75, 'Cucharón 36", llaves, extintor', 0.00, 'Rayón en cabina.', 1, 1);

INSERT INTO dano (descripcion, severidad, costoEstimado, cargoAlCliente, estado, idDetalleDevolucion) VALUES
 ('Rayón superficial en puerta de cabina.', 'Leve', 45.00, FALSE, 'Resuelto', 1);

UPDATE contrato SET estado = 'Finalizado' WHERE idContrato = 1;

-- ---------------------------------------------------------------------
-- Mantenimiento
-- ---------------------------------------------------------------------
INSERT INTO mantenimiento (tipo, descripcion, fechaIngreso, fechaSalidaEstimada, fechaSalidaReal, costo, estado, proveedorServicio, idEquipo, idRegistradoPor) VALUES
 ('Correctivo', 'Falla en bomba de inyección.',  TIMESTAMP(DATE_SUB(@hoy, INTERVAL 3 DAY), '10:00:00'), TIMESTAMP(DATE_ADD(@hoy, INTERVAL 4 DAY), '17:00:00'), NULL, 0.00, 'EsperandoRepuesto', 'Taller Maquinaria Pesada S.A.', 5, 2),
 ('Preventivo', 'Mantenimiento preventivo 500 h.', TIMESTAMP(DATE_SUB(@hoy, INTERVAL 40 DAY), '08:00:00'), TIMESTAMP(DATE_SUB(@hoy, INTERVAL 38 DAY), '17:00:00'), TIMESTAMP(DATE_SUB(@hoy, INTERVAL 38 DAY), '15:30:00'), 185.00, 'Finalizado', 'Taller interno', 1, 3);

COMMIT;

SET @app_idUsuario = NULL;
SET @app_motivo = NULL;
