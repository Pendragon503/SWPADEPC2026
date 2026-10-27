-- =====================================================================
--  EquipRent - Sistema Web para Alquiler de Equipos para Construcción
--  Script 01: Esquema de base de datos
--  Motor: MySQL 8.0.16 o superior (requiere CHECK y triggers)
--  Grupo de Proyecto 3 - TPI - Universidad de El Salvador
-- ---------------------------------------------------------------------
--  ADVERTENCIA: este script BORRA y vuelve a crear la base "equiprent".
--  Úsalo solo en desarrollo.
--
--  Convenciones:
--   * Tablas y campos en camelCase, igual que el diccionario de datos.
--   * daño -> "dano" y devolución -> "devolucion" (sin tildes ni ñ).
--   * Fechas de auditoría en TIMESTAMP; fechas de negocio en DATE/DATETIME.
--   * Los estados se guardan como VARCHAR y se validan con CHECK.
-- =====================================================================

DROP DATABASE IF EXISTS equiprent;
CREATE DATABASE equiprent
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_spanish_ci;
USE equiprent;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. rol: perfiles de acceso (Administrador, Operador, Cliente)
-- ---------------------------------------------------------------------
CREATE TABLE rol (
  idRol        SMALLINT     NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(30)  NOT NULL,
  descripcion  VARCHAR(150) NULL,
  activo       BOOLEAN      NOT NULL DEFAULT TRUE,
  CONSTRAINT pk_rol PRIMARY KEY (idRol),
  CONSTRAINT uq_rol_nombre UNIQUE (nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. usuario: cuentas de acceso de todos los perfiles
-- ---------------------------------------------------------------------
CREATE TABLE usuario (
  idUsuario           INT          NOT NULL AUTO_INCREMENT,
  nombres             VARCHAR(80)  NOT NULL,
  apellidos           VARCHAR(80)  NOT NULL,
  correo              VARCHAR(120) NOT NULL,
  claveHash           VARCHAR(255) NOT NULL,
  telefono            VARCHAR(20)  NULL,
  documentoIdentidad  VARCHAR(25)  NULL,
  direccion           VARCHAR(250) NULL,
  activo              BOOLEAN      NOT NULL DEFAULT TRUE,
  fechaRegistro       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimoAcceso        TIMESTAMP    NULL,
  idRol               SMALLINT     NOT NULL,
  CONSTRAINT pk_usuario PRIMARY KEY (idUsuario),
  CONSTRAINT uq_usuario_correo UNIQUE (correo),
  CONSTRAINT uq_usuario_documento UNIQUE (documentoIdentidad),
  CONSTRAINT fk_usuario_rol FOREIGN KEY (idRol) REFERENCES rol (idRol)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_usuario_correo CHECK (correo LIKE '%_@_%._%')
) ENGINE=InnoDB;

CREATE INDEX ix_usuario_rol ON usuario (idRol);

-- ---------------------------------------------------------------------
-- 3. cliente: datos comerciales; relación 1:1 con usuario
--    codigoCliente (RF-03) lo asigna la aplicación justo después del
--    INSERT, dentro de la misma transacción: CLI-000001
-- ---------------------------------------------------------------------
CREATE TABLE cliente (
  idCliente            INT          NOT NULL AUTO_INCREMENT,
  codigoCliente        VARCHAR(15)  NULL,
  tipoPersona          VARCHAR(20)  NOT NULL,
  documentoTributario  VARCHAR(25)  NULL,
  razonSocial          VARCHAR(150) NULL,
  contactoAlterno      VARCHAR(120) NULL,
  telefonoAlterno      VARCHAR(20)  NULL,
  observaciones        VARCHAR(500) NULL,
  fechaRegistro        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  idUsuario            INT          NOT NULL,
  CONSTRAINT pk_cliente PRIMARY KEY (idCliente),
  CONSTRAINT uq_cliente_codigo UNIQUE (codigoCliente),
  CONSTRAINT uq_cliente_doctributario UNIQUE (documentoTributario),
  CONSTRAINT uq_cliente_usuario UNIQUE (idUsuario),
  CONSTRAINT fk_cliente_usuario FOREIGN KEY (idUsuario) REFERENCES usuario (idUsuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_cliente_tipo CHECK (tipoPersona IN ('Natural', 'Juridica')),
  CONSTRAINT ck_cliente_razon CHECK (tipoPersona = 'Natural' OR razonSocial IS NOT NULL)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. categoria
-- ---------------------------------------------------------------------
CREATE TABLE categoria (
  idCategoria    SMALLINT     NOT NULL AUTO_INCREMENT,
  nombre         VARCHAR(80)  NOT NULL,
  descripcion    VARCHAR(250) NULL,
  activa         BOOLEAN      NOT NULL DEFAULT TRUE,
  fechaRegistro  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT pk_categoria PRIMARY KEY (idCategoria),
  CONSTRAINT uq_categoria_nombre UNIQUE (nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. equipo: cada fila es UNA unidad física (tiene su número de serie).
--    La "cantidad" que muestra Figma se obtiene agrupando por modelo.
--    Estados:
--      Disponible    -> puede reservarse
--      Alquilado     -> está en obra con un cliente
--      Revision      -> devuelto con daños, pendiente de inspección
--      Mantenimiento -> orden de mantenimiento abierta
--      FueraServicio -> retirado de operación
-- ---------------------------------------------------------------------
CREATE TABLE equipo (
  idEquipo               INT           NOT NULL AUTO_INCREMENT,
  codigoInventario       VARCHAR(30)   NOT NULL,
  nombre                 VARCHAR(120)  NOT NULL,
  marca                  VARCHAR(80)   NULL,
  modelo                 VARCHAR(80)   NULL,
  numeroSerie            VARCHAR(80)   NULL,
  descripcion            TEXT          NULL,
  caracteristicasTecnicas TEXT         NULL,
  condicionesUso         TEXT          NULL,
  estado                 VARCHAR(20)   NOT NULL DEFAULT 'Disponible',
  activo                 BOOLEAN       NOT NULL DEFAULT TRUE,
  fechaAdquisicion       DATE          NULL,
  valorReferencia        DECIMAL(12,2) NULL,
  idCategoria            SMALLINT      NOT NULL,
  CONSTRAINT pk_equipo PRIMARY KEY (idEquipo),
  CONSTRAINT uq_equipo_codigo UNIQUE (codigoInventario),
  CONSTRAINT uq_equipo_serie UNIQUE (numeroSerie),
  CONSTRAINT fk_equipo_categoria FOREIGN KEY (idCategoria) REFERENCES categoria (idCategoria)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_equipo_estado CHECK (estado IN ('Disponible', 'Alquilado', 'Revision', 'Mantenimiento', 'FueraServicio')),
  CONSTRAINT ck_equipo_valor CHECK (valorReferencia IS NULL OR valorReferencia >= 0)
) ENGINE=InnoDB;

CREATE INDEX ix_equipo_categoria ON equipo (idCategoria);
CREATE INDEX ix_equipo_estado ON equipo (estado, activo);

-- ---------------------------------------------------------------------
-- 6. fotografiaEquipo: galería; solo una foto principal por equipo
-- ---------------------------------------------------------------------
CREATE TABLE fotografiaEquipo (
  idFotografia        INT          NOT NULL AUTO_INCREMENT,
  urlFotografia       VARCHAR(500) NOT NULL,
  textoAlternativo    VARCHAR(150) NULL,
  ordenVisualizacion  SMALLINT     NOT NULL DEFAULT 1,
  esPrincipal         BOOLEAN      NOT NULL DEFAULT FALSE,
  idEquipo            INT          NOT NULL,
  -- Columna auxiliar: vale idEquipo solo si es principal; el UNIQUE
  -- impide que un equipo tenga dos fotos principales.
  principalDe         INT GENERATED ALWAYS AS (IF(esPrincipal, idEquipo, NULL)) STORED,
  CONSTRAINT pk_fotografiaEquipo PRIMARY KEY (idFotografia),
  CONSTRAINT uq_fotografia_principal UNIQUE (principalDe),
  -- MySQL no permite CASCADE en la base de una columna generada STORED;
  -- de todos modos los equipos no se borran, se desactivan.
  CONSTRAINT fk_fotografia_equipo FOREIGN KEY (idEquipo) REFERENCES equipo (idEquipo)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT ck_fotografia_orden CHECK (ordenVisualizacion >= 1)
) ENGINE=InnoDB;

CREATE INDEX ix_fotografia_equipo ON fotografiaEquipo (idEquipo, ordenVisualizacion);

-- ---------------------------------------------------------------------
-- 7. tarifa: precios por período con vigencia
-- ---------------------------------------------------------------------
CREATE TABLE tarifa (
  idTarifa            INT           NOT NULL AUTO_INCREMENT,
  tipoPeriodo         VARCHAR(20)   NOT NULL,
  monto               DECIMAL(10,2) NOT NULL,
  depositoSugerido    DECIMAL(10,2) NOT NULL DEFAULT 0,
  fechaInicioVigencia DATE          NOT NULL,
  fechaFinVigencia    DATE          NULL,
  activa              BOOLEAN       NOT NULL DEFAULT TRUE,
  idEquipo            INT           NOT NULL,
  CONSTRAINT pk_tarifa PRIMARY KEY (idTarifa),
  CONSTRAINT fk_tarifa_equipo FOREIGN KEY (idEquipo) REFERENCES equipo (idEquipo)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_tarifa_periodo CHECK (tipoPeriodo IN ('Dia', 'Semana', 'Mes')),
  CONSTRAINT ck_tarifa_monto CHECK (monto > 0),
  CONSTRAINT ck_tarifa_deposito CHECK (depositoSugerido >= 0),
  CONSTRAINT ck_tarifa_vigencia CHECK (fechaFinVigencia IS NULL OR fechaFinVigencia >= fechaInicioVigencia)
) ENGINE=InnoDB;

CREATE INDEX ix_tarifa_equipo ON tarifa (idEquipo, tipoPeriodo, activa);

-- ---------------------------------------------------------------------
-- 8. contrato: solicitud / contrato de alquiler
--    Estados:  Pendiente -> Aprobado -> Activo (entregado) -> Finalizado
--              Pendiente -> Rechazado | Cancelado
--    Pendiente, Aprobado y Activo BLOQUEAN las fechas del equipo.
--    numeroContrato (ALQ-2026-00001) lo asigna la aplicación justo después
--    del INSERT, en la misma transacción.
--    totalEstimado = subtotal + depositoGarantia
-- ---------------------------------------------------------------------
CREATE TABLE contrato (
  idContrato        BIGINT        NOT NULL AUTO_INCREMENT,
  numeroContrato    VARCHAR(25)   NULL,
  fechaSolicitud    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fechaInicio       DATE          NOT NULL,
  fechaFin          DATE          NOT NULL,
  destino           VARCHAR(250)  NULL,
  estado            VARCHAR(20)   NOT NULL DEFAULT 'Pendiente',
  subtotal          DECIMAL(12,2) NOT NULL DEFAULT 0,
  depositoGarantia  DECIMAL(12,2) NOT NULL DEFAULT 0,
  totalEstimado     DECIMAL(12,2) NOT NULL DEFAULT 0,
  observaciones     VARCHAR(500)  NULL,
  motivoRechazo     VARCHAR(300)  NULL,
  fechaAprobacion   TIMESTAMP     NULL,
  idCliente         INT           NOT NULL,
  idGestionadoPor   INT           NULL,
  CONSTRAINT pk_contrato PRIMARY KEY (idContrato),
  CONSTRAINT uq_contrato_numero UNIQUE (numeroContrato),
  CONSTRAINT fk_contrato_cliente FOREIGN KEY (idCliente) REFERENCES cliente (idCliente)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_contrato_gestor FOREIGN KEY (idGestionadoPor) REFERENCES usuario (idUsuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_contrato_estado CHECK (estado IN ('Pendiente', 'Aprobado', 'Rechazado', 'Activo', 'Finalizado', 'Cancelado')),
  CONSTRAINT ck_contrato_fechas CHECK (fechaFin >= fechaInicio),
  CONSTRAINT ck_contrato_montos CHECK (subtotal >= 0 AND depositoGarantia >= 0),
  CONSTRAINT ck_contrato_total CHECK (totalEstimado = subtotal + depositoGarantia),
  CONSTRAINT ck_contrato_rechazo CHECK (estado <> 'Rechazado' OR motivoRechazo IS NOT NULL)
) ENGINE=InnoDB;

CREATE INDEX ix_contrato_estado ON contrato (estado);
CREATE INDEX ix_contrato_cliente ON contrato (idCliente);
CREATE INDEX ix_contrato_fechas ON contrato (fechaInicio, fechaFin);

-- ---------------------------------------------------------------------
-- 9. detalleContrato: una línea por unidad de equipo
--    diasFacturados = DATEDIFF(fechaFin, fechaInicio) + 1 (días inclusivos)
-- ---------------------------------------------------------------------
CREATE TABLE detalleContrato (
  idDetalleContrato    BIGINT        NOT NULL AUTO_INCREMENT,
  precioDiarioAplicado DECIMAL(10,2) NOT NULL,
  diasFacturados       SMALLINT      NOT NULL,
  subtotalLinea        DECIMAL(12,2) NOT NULL,
  condicionesPactadas  VARCHAR(500)  NULL,
  idContrato           BIGINT        NOT NULL,
  idEquipo             INT           NOT NULL,
  CONSTRAINT pk_detalleContrato PRIMARY KEY (idDetalleContrato),
  CONSTRAINT uq_detalleContrato_equipo UNIQUE (idContrato, idEquipo),
  CONSTRAINT fk_detalleContrato_contrato FOREIGN KEY (idContrato) REFERENCES contrato (idContrato)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_detalleContrato_equipo FOREIGN KEY (idEquipo) REFERENCES equipo (idEquipo)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_detalleContrato_precio CHECK (precioDiarioAplicado > 0),
  CONSTRAINT ck_detalleContrato_dias CHECK (diasFacturados > 0),
  CONSTRAINT ck_detalleContrato_subtotal CHECK (subtotalLinea = precioDiarioAplicado * diasFacturados)
) ENGINE=InnoDB;

CREATE INDEX ix_detalleContrato_equipo ON detalleContrato (idEquipo);

-- ---------------------------------------------------------------------
-- 10. entrega: salida de equipos (puede haber entregas parciales)
-- ---------------------------------------------------------------------
CREATE TABLE entrega (
  idEntrega           BIGINT       NOT NULL AUTO_INCREMENT,
  fechaEntrega        DATETIME     NOT NULL,
  nombreReceptor      VARCHAR(160) NOT NULL,
  documentoReceptor   VARCHAR(25)  NOT NULL,
  observaciones       VARCHAR(500) NULL,
  idContrato          BIGINT       NOT NULL,
  idRegistradoPor     INT          NOT NULL,
  CONSTRAINT pk_entrega PRIMARY KEY (idEntrega),
  CONSTRAINT fk_entrega_contrato FOREIGN KEY (idContrato) REFERENCES contrato (idContrato)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_entrega_usuario FOREIGN KEY (idRegistradoPor) REFERENCES usuario (idUsuario)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX ix_entrega_contrato ON entrega (idContrato);
CREATE INDEX ix_entrega_fecha ON entrega (fechaEntrega);

-- ---------------------------------------------------------------------
-- 11. detalleEntrega: estado de cada unidad al salir.
--     UNIQUE(idDetalleContrato): una unidad no se entrega dos veces (RF-09)
-- ---------------------------------------------------------------------
CREATE TABLE detalleEntrega (
  idDetalleEntrega     BIGINT        NOT NULL AUTO_INCREMENT,
  estadoFisico         VARCHAR(300)  NOT NULL,
  nivelCombustible     DECIMAL(5,2)  NULL,
  lecturaHorometro     DECIMAL(10,2) NULL,
  accesoriosEntregados VARCHAR(300)  NULL,
  observaciones        VARCHAR(500)  NULL,
  idEntrega            BIGINT        NOT NULL,
  idDetalleContrato    BIGINT        NOT NULL,
  CONSTRAINT pk_detalleEntrega PRIMARY KEY (idDetalleEntrega),
  CONSTRAINT uq_detalleEntrega_linea UNIQUE (idDetalleContrato),
  CONSTRAINT fk_detalleEntrega_entrega FOREIGN KEY (idEntrega) REFERENCES entrega (idEntrega)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_detalleEntrega_linea FOREIGN KEY (idDetalleContrato) REFERENCES detalleContrato (idDetalleContrato)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_detalleEntrega_combustible CHECK (nivelCombustible IS NULL OR nivelCombustible BETWEEN 0 AND 100),
  CONSTRAINT ck_detalleEntrega_horometro CHECK (lecturaHorometro IS NULL OR lecturaHorometro >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. devolucion: recepción de equipos (puede haber devoluciones parciales)
-- ---------------------------------------------------------------------
CREATE TABLE devolucion (
  idDevolucion       BIGINT        NOT NULL AUTO_INCREMENT,
  fechaDevolucion    DATETIME      NOT NULL,
  diasAtraso         SMALLINT      NOT NULL DEFAULT 0,
  penalizacionTotal  DECIMAL(12,2) NOT NULL DEFAULT 0,
  observaciones      VARCHAR(500)  NULL,
  idContrato         BIGINT        NOT NULL,
  idRegistradoPor    INT           NOT NULL,
  CONSTRAINT pk_devolucion PRIMARY KEY (idDevolucion),
  CONSTRAINT fk_devolucion_contrato FOREIGN KEY (idContrato) REFERENCES contrato (idContrato)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_devolucion_usuario FOREIGN KEY (idRegistradoPor) REFERENCES usuario (idUsuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_devolucion_atraso CHECK (diasAtraso >= 0),
  CONSTRAINT ck_devolucion_penalizacion CHECK (penalizacionTotal >= 0)
) ENGINE=InnoDB;

CREATE INDEX ix_devolucion_contrato ON devolucion (idContrato);
CREATE INDEX ix_devolucion_fecha ON devolucion (fechaDevolucion);

-- ---------------------------------------------------------------------
-- 13. detalleDevolucion: estado de cada unidad al regresar.
--     UNIQUE(idDetalleContrato): una unidad no se recibe dos veces (RF-10)
-- ---------------------------------------------------------------------
CREATE TABLE detalleDevolucion (
  idDetalleDevolucion  BIGINT        NOT NULL AUTO_INCREMENT,
  estadoFisico         VARCHAR(300)  NOT NULL,
  nivelCombustible     DECIMAL(5,2)  NULL,
  lecturaHorometro     DECIMAL(10,2) NULL,
  accesoriosRecibidos  VARCHAR(300)  NULL,
  penalizacion         DECIMAL(10,2) NOT NULL DEFAULT 0,
  observaciones        VARCHAR(500)  NULL,
  idDevolucion         BIGINT        NOT NULL,
  idDetalleContrato    BIGINT        NOT NULL,
  CONSTRAINT pk_detalleDevolucion PRIMARY KEY (idDetalleDevolucion),
  CONSTRAINT uq_detalleDevolucion_linea UNIQUE (idDetalleContrato),
  CONSTRAINT fk_detalleDevolucion_devolucion FOREIGN KEY (idDevolucion) REFERENCES devolucion (idDevolucion)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_detalleDevolucion_linea FOREIGN KEY (idDetalleContrato) REFERENCES detalleContrato (idDetalleContrato)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_detalleDevolucion_combustible CHECK (nivelCombustible IS NULL OR nivelCombustible BETWEEN 0 AND 100),
  CONSTRAINT ck_detalleDevolucion_horometro CHECK (lecturaHorometro IS NULL OR lecturaHorometro >= 0),
  CONSTRAINT ck_detalleDevolucion_penalizacion CHECK (penalizacion >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 14. dano: daños detectados en una devolución
-- ---------------------------------------------------------------------
CREATE TABLE dano (
  idDano               BIGINT        NOT NULL AUTO_INCREMENT,
  descripcion          VARCHAR(500)  NOT NULL,
  severidad            VARCHAR(20)   NOT NULL,
  costoEstimado        DECIMAL(10,2) NOT NULL DEFAULT 0,
  cargoAlCliente       BOOLEAN       NOT NULL DEFAULT FALSE,
  estado               VARCHAR(20)   NOT NULL DEFAULT 'Reportado',
  urlEvidencia         VARCHAR(500)  NULL,
  fechaRegistro        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  idDetalleDevolucion  BIGINT        NOT NULL,
  CONSTRAINT pk_dano PRIMARY KEY (idDano),
  CONSTRAINT fk_dano_detalleDevolucion FOREIGN KEY (idDetalleDevolucion) REFERENCES detalleDevolucion (idDetalleDevolucion)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_dano_severidad CHECK (severidad IN ('Leve', 'Moderado', 'Grave')),
  CONSTRAINT ck_dano_estado CHECK (estado IN ('Reportado', 'EnEvaluacion', 'EnReparacion', 'Resuelto', 'Descartado')),
  CONSTRAINT ck_dano_costo CHECK (costoEstimado >= 0)
) ENGINE=InnoDB;

CREATE INDEX ix_dano_detalleDevolucion ON dano (idDetalleDevolucion);

-- ---------------------------------------------------------------------
-- 15. mantenimiento: órdenes preventivas y correctivas.
--     Una orden está ABIERTA mientras su estado no sea Finalizado ni
--     Cancelado; en ese tiempo el equipo no se puede reservar (RF-12).
-- ---------------------------------------------------------------------
CREATE TABLE mantenimiento (
  idMantenimiento      BIGINT        NOT NULL AUTO_INCREMENT,
  tipo                 VARCHAR(30)   NOT NULL,
  descripcion          VARCHAR(500)  NOT NULL,
  fechaIngreso         DATETIME      NOT NULL,
  fechaSalidaEstimada  DATETIME      NULL,
  fechaSalidaReal      DATETIME      NULL,
  costo                DECIMAL(10,2) NOT NULL DEFAULT 0,
  estado               VARCHAR(20)   NOT NULL DEFAULT 'Diagnostico',
  proveedorServicio    VARCHAR(150)  NULL,
  idEquipo             INT           NOT NULL,
  idRegistradoPor      INT           NOT NULL,
  CONSTRAINT pk_mantenimiento PRIMARY KEY (idMantenimiento),
  CONSTRAINT fk_mantenimiento_equipo FOREIGN KEY (idEquipo) REFERENCES equipo (idEquipo)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mantenimiento_usuario FOREIGN KEY (idRegistradoPor) REFERENCES usuario (idUsuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_mantenimiento_tipo CHECK (tipo IN ('Preventivo', 'Correctivo')),
  CONSTRAINT ck_mantenimiento_estado CHECK (estado IN ('Diagnostico', 'EnReparacion', 'EsperandoRepuesto', 'Finalizado', 'Cancelado')),
  CONSTRAINT ck_mantenimiento_costo CHECK (costo >= 0),
  CONSTRAINT ck_mantenimiento_fechas CHECK (fechaSalidaReal IS NULL OR fechaSalidaReal >= fechaIngreso),
  CONSTRAINT ck_mantenimiento_cierre CHECK (estado <> 'Finalizado' OR fechaSalidaReal IS NOT NULL)
) ENGINE=InnoDB;

CREATE INDEX ix_mantenimiento_equipo ON mantenimiento (idEquipo, estado);

-- ---------------------------------------------------------------------
-- 16. bitacoraEstadoEquipo: historial automático de cambios de estado
--     (lo llenan los triggers de la tabla equipo)
-- ---------------------------------------------------------------------
CREATE TABLE bitacoraEstadoEquipo (
  idBitacora      BIGINT       NOT NULL AUTO_INCREMENT,
  estadoAnterior  VARCHAR(30)  NULL,
  estadoNuevo     VARCHAR(30)  NOT NULL,
  motivo          VARCHAR(300) NOT NULL,
  fechaCambio     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  idEquipo        INT          NOT NULL,
  idUsuario       INT          NOT NULL,
  CONSTRAINT pk_bitacoraEstadoEquipo PRIMARY KEY (idBitacora),
  CONSTRAINT fk_bitacora_equipo FOREIGN KEY (idEquipo) REFERENCES equipo (idEquipo)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_bitacora_usuario FOREIGN KEY (idUsuario) REFERENCES usuario (idUsuario)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX ix_bitacora_equipo ON bitacoraEstadoEquipo (idEquipo, fechaCambio);

-- =====================================================================
--  FUNCIONES Y PROCEDIMIENTOS (Proceso 1 de la Etapa 1)
-- =====================================================================
DELIMITER $$

-- Cuenta las reservas que chocan con [pInicio, pFin] para una unidad.
-- Reglas:
--  * Bloquean los contratos Pendiente, Aprobado y Activo.
--  * Dos rangos se cruzan si inicioA <= finB y finA >= inicioB.
--  * Una unidad ya devuelta (tiene detalleDevolucion) deja de bloquear.
--  * Un contrato Activo vencido sin devolución bloquea indefinidamente,
--    porque no sabemos cuándo regresará la unidad.
--  pExcluirContrato: contrato a ignorar (el que se está registrando); NULL = ninguno.
CREATE FUNCTION fn_conflictosEquipo(
  pIdEquipo INT,
  pInicio DATE,
  pFin DATE,
  pExcluirContrato BIGINT
) RETURNS INT
READS SQL DATA
BEGIN
  DECLARE vConflictos INT DEFAULT 0;

  SELECT COUNT(*) INTO vConflictos
    FROM detalleContrato dc
    JOIN contrato c ON c.idContrato = dc.idContrato
   WHERE dc.idEquipo = pIdEquipo
     AND (pExcluirContrato IS NULL OR dc.idContrato <> pExcluirContrato)
     AND c.estado IN ('Pendiente', 'Aprobado', 'Activo')
     AND c.fechaInicio <= pFin
     AND (CASE WHEN c.estado = 'Activo' AND c.fechaFin < CURDATE()
               THEN DATE('9999-12-31')
               ELSE c.fechaFin END) >= pInicio
     AND NOT EXISTS (SELECT 1 FROM detalleDevolucion dd
                      WHERE dd.idDetalleContrato = dc.idDetalleContrato);

  RETURN vConflictos;
END$$

-- Lista los equipos que se pueden alquilar en [pInicio, pFin].
-- pIdCategoria = NULL devuelve todas las categorías.
CREATE PROCEDURE sp_equiposDisponibles(
  IN pInicio DATE,
  IN pFin DATE,
  IN pIdCategoria SMALLINT
)
READS SQL DATA
BEGIN
  IF pInicio IS NULL OR pFin IS NULL OR pFin < pInicio THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Rango de fechas invalido: la fecha final no puede ser anterior a la inicial.';
  END IF;
  IF pInicio < CURDATE() THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La fecha inicial no puede ser anterior a hoy.';
  END IF;

  SELECT v.idEquipo,
         v.codigoInventario,
         v.nombre,
         v.marca,
         v.modelo,
         v.idCategoria,
         v.categoria,
         v.caracteristicasTecnicas,
         v.fotoPrincipal,
         v.precioDiario,
         v.depositoSugerido,
         DATEDIFF(pFin, pInicio) + 1 AS dias,
         v.precioDiario * (DATEDIFF(pFin, pInicio) + 1) AS subtotalEstimado
    FROM v_catalogoEquipo v
   WHERE v.activo = TRUE
     -- "Alquilado" no excluye: puede estar libre en fechas futuras.
     AND v.estado IN ('Disponible', 'Alquilado')
     AND v.precioDiario IS NOT NULL
     AND (pIdCategoria IS NULL OR v.idCategoria = pIdCategoria)
     AND NOT EXISTS (SELECT 1 FROM mantenimiento m
                      WHERE m.idEquipo = v.idEquipo
                        AND m.estado NOT IN ('Finalizado', 'Cancelado'))
     AND fn_conflictosEquipo(v.idEquipo, pInicio, pFin, NULL) = 0
   ORDER BY v.categoria, v.nombre, v.codigoInventario;
END$$

-- =====================================================================
--  TRIGGERS (reglas de negocio que la base garantiza por sí misma)
-- =====================================================================

-- R-04 / RF-05: una unidad no puede quedar en dos contratos vigentes con
-- fechas que se crucen. Es la última línea de defensa: la aplicación ya
-- valida antes, pero esto protege contra solicitudes simultáneas.
CREATE TRIGGER trg_detalleContrato_bi
BEFORE INSERT ON detalleContrato
FOR EACH ROW
BEGIN
  DECLARE vInicio DATE;
  DECLARE vFin DATE;
  DECLARE vEstado VARCHAR(20);

  SELECT fechaInicio, fechaFin, estado
    INTO vInicio, vFin, vEstado
    FROM contrato
   WHERE idContrato = NEW.idContrato;

  IF vEstado IN ('Pendiente', 'Aprobado', 'Activo') THEN
    IF fn_conflictosEquipo(NEW.idEquipo, vInicio, vFin, NEW.idContrato) > 0 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El equipo ya esta reservado en fechas que se cruzan con las solicitadas.';
    END IF;
  END IF;
END$$

-- RF-09: solo se entregan líneas del mismo contrato y si está Aprobado o Activo
CREATE TRIGGER trg_detalleEntrega_bi
BEFORE INSERT ON detalleEntrega
FOR EACH ROW
BEGIN
  DECLARE vContratoEntrega BIGINT;
  DECLARE vContratoLinea BIGINT;
  DECLARE vEstado VARCHAR(20);

  SELECT idContrato INTO vContratoEntrega FROM entrega WHERE idEntrega = NEW.idEntrega;
  SELECT dc.idContrato, c.estado INTO vContratoLinea, vEstado
    FROM detalleContrato dc JOIN contrato c ON c.idContrato = dc.idContrato
   WHERE dc.idDetalleContrato = NEW.idDetalleContrato;

  IF vContratoEntrega <> vContratoLinea THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El equipo no pertenece al contrato de esta entrega.';
  END IF;
  IF vEstado NOT IN ('Aprobado', 'Activo') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Solo se pueden entregar equipos de contratos aprobados o activos.';
  END IF;
END$$

-- RF-10: solo se reciben líneas del mismo contrato que ya fueron entregadas
CREATE TRIGGER trg_detalleDevolucion_bi
BEFORE INSERT ON detalleDevolucion
FOR EACH ROW
BEGIN
  DECLARE vContratoDevolucion BIGINT;
  DECLARE vContratoLinea BIGINT;

  SELECT idContrato INTO vContratoDevolucion FROM devolucion WHERE idDevolucion = NEW.idDevolucion;
  SELECT idContrato INTO vContratoLinea FROM detalleContrato WHERE idDetalleContrato = NEW.idDetalleContrato;

  IF vContratoDevolucion <> vContratoLinea THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El equipo no pertenece al contrato de esta devolucion.';
  END IF;
  IF NOT EXISTS (SELECT 1 FROM detalleEntrega WHERE idDetalleContrato = NEW.idDetalleContrato) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'No se puede recibir un equipo que no ha sido entregado.';
  END IF;
END$$

-- Bitácora automática. La aplicación debe indicar quién hace el cambio:
--   SET @app_idUsuario = <id del usuario en sesión>;
--   SET @app_motivo    = 'texto opcional';
CREATE TRIGGER trg_equipo_ai
AFTER INSERT ON equipo
FOR EACH ROW
BEGIN
  IF @app_idUsuario IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Falta @app_idUsuario para registrar la bitacora del equipo.';
  END IF;
  INSERT INTO bitacoraEstadoEquipo (estadoAnterior, estadoNuevo, motivo, idEquipo, idUsuario)
  VALUES (NULL, NEW.estado, COALESCE(@app_motivo, 'Registro inicial del equipo'), NEW.idEquipo, @app_idUsuario);
END$$

CREATE TRIGGER trg_equipo_au
AFTER UPDATE ON equipo
FOR EACH ROW
BEGIN
  IF NOT (NEW.estado <=> OLD.estado) THEN
    IF @app_idUsuario IS NULL THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Falta @app_idUsuario para registrar la bitacora del equipo.';
    END IF;
    INSERT INTO bitacoraEstadoEquipo (estadoAnterior, estadoNuevo, motivo, idEquipo, idUsuario)
    VALUES (OLD.estado, NEW.estado, COALESCE(@app_motivo, 'Cambio de estado'), NEW.idEquipo, @app_idUsuario);
  END IF;
END$$

DELIMITER ;

-- =====================================================================
--  VISTAS
-- =====================================================================

-- Tarifa diaria vigente de cada equipo: exactamente UNA fila por equipo.
-- Si hay varias vigentes gana la de inicio más reciente y, si empatan,
-- la registrada de último (idTarifa mayor).
CREATE VIEW v_tarifaDiariaVigente AS
SELECT t.idEquipo, t.idTarifa, t.monto AS precioDiario, t.depositoSugerido
  FROM tarifa t
 WHERE t.idTarifa = (
        SELECT t2.idTarifa
          FROM tarifa t2
         WHERE t2.idEquipo = t.idEquipo
           AND t2.tipoPeriodo = 'Dia'
           AND t2.activa = TRUE
           AND t2.fechaInicioVigencia <= CURDATE()
           AND (t2.fechaFinVigencia IS NULL OR t2.fechaFinVigencia >= CURDATE())
         ORDER BY t2.fechaInicioVigencia DESC, t2.idTarifa DESC
         LIMIT 1);

-- Catálogo: equipo + categoría + tarifa diaria + foto principal
CREATE VIEW v_catalogoEquipo AS
SELECT e.idEquipo,
       e.codigoInventario,
       e.nombre,
       e.marca,
       e.modelo,
       e.descripcion,
       e.caracteristicasTecnicas,
       e.condicionesUso,
       e.estado,
       e.activo,
       c.idCategoria,
       c.nombre AS categoria,
       tv.precioDiario,
       tv.depositoSugerido,
       f.urlFotografia AS fotoPrincipal
  FROM equipo e
  JOIN categoria c ON c.idCategoria = e.idCategoria
  LEFT JOIN v_tarifaDiariaVigente tv ON tv.idEquipo = e.idEquipo
  LEFT JOIN fotografiaEquipo f ON f.idEquipo = e.idEquipo AND f.esPrincipal = TRUE;

-- ---------------------------------------------------------------------
-- 17. reporteGenerado: historial de reportes descargados desde
--     Configuración → Ver reportes. No guarda archivos: volver a
--     descargar regenera el reporte con los mismos parámetros.
--     tipo: alquileres | inventario | fallas | salidas
-- ---------------------------------------------------------------------
CREATE TABLE reporteGenerado (
  idReporte        INT          NOT NULL AUTO_INCREMENT,
  tipo             VARCHAR(20)  NOT NULL,
  fechaInicio      DATE         NOT NULL,
  fechaFin         DATE         NOT NULL,
  formato          VARCHAR(10)  NOT NULL,
  idUsuario        INT          NOT NULL,
  fechaGeneracion  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT pk_reporteGenerado PRIMARY KEY (idReporte),
  CONSTRAINT fk_reporteGenerado_usuario FOREIGN KEY (idUsuario) REFERENCES usuario (idUsuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_reporteGenerado_tipo CHECK (tipo IN ('alquileres', 'inventario', 'fallas', 'salidas')),
  CONSTRAINT ck_reporteGenerado_formato CHECK (formato IN ('PDF', 'Excel')),
  CONSTRAINT ck_reporteGenerado_fechas CHECK (fechaFin >= fechaInicio)
) ENGINE=InnoDB;

CREATE INDEX ix_reporteGenerado_fecha ON reporteGenerado (fechaGeneracion);
