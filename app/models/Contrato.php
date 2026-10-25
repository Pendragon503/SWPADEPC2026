<?php
/**
 * EquipRent - Modelo de contratos (tablas contrato + detalleContrato).
 *
 * Lo usan "Mis alquileres" del cliente y Operación (solicitudes, contratos; RF-06, RF-08).
 * Las consultas del cliente filtran por idCliente: un cliente nunca ve ni cancela
 * contratos ajenos. Aprobar, rechazar y cancelar son UPDATE condicionados al estado
 * Pendiente: si dos personas lo intentan a la vez, solo la primera lo cambia.
 */
declare(strict_types=1);

class Contrato
{
    /** Estados de la base, en el orden en que se muestran en el filtro. */
    public const ESTADOS = ['Pendiente', 'Aprobado', 'Activo', 'Finalizado', 'Rechazado', 'Cancelado'];

    /** Filtros de buscar() y contar(), con su valor "sin filtro". */
    public const FILTROS = [
        'numero'    => '',  // número de contrato (parte)
        'cliente'   => '',  // código, nombre o razón social (parte)
        'equipo'    => '',  // nombre o código de inventario de alguno de sus equipos (parte)
        'desde'     => '',  // AAAA-MM-DD: contratos cuyo período se cruza con [desde, hasta]
        'hasta'     => '',
        'estado'    => '',
        'idCliente' => 0,   // solo los de ese cliente ("Mis alquileres")
        'pendiente' => '',  // 'entrega' | 'devolucion': con equipos por entregar o por recibir
    ];

    /** Condición de cada lista de pendientes (alias c = contrato). */
    private const PENDIENTES = [
        // Aprobados, o Activos con una entrega parcial
        'entrega'    => "c.estado IN ('Aprobado', 'Activo') AND EXISTS (
                            SELECT 1 FROM detalleContrato p
                             WHERE p.idContrato = c.idContrato
                               AND NOT EXISTS (SELECT 1 FROM detalleEntrega x WHERE x.idDetalleContrato = p.idDetalleContrato))",
        // Activos con algún equipo entregado que todavía no regresa
        'devolucion' => "c.estado = 'Activo' AND EXISTS (
                            SELECT 1 FROM detalleContrato p
                              JOIN detalleEntrega x ON x.idDetalleContrato = p.idDetalleContrato
                             WHERE p.idContrato = c.idContrato
                               AND NOT EXISTS (SELECT 1 FROM detalleDevolucion y WHERE y.idDetalleContrato = p.idDetalleContrato))",
    ];

    /** Órdenes de buscar(): las solicitudes se atienden de la más antigua a la más reciente. */
    private const ORDENES = [
        'recientes' => 'c.fechaSolicitud DESC, c.idContrato DESC',
        'antiguos'  => 'c.fechaSolicitud, c.idContrato',
    ];

    /** Largo máximo de contrato.motivoRechazo. */
    private const LARGO_MOTIVO = 300;

    // ---------------------------------------------------------------------
    // Reglas
    // ---------------------------------------------------------------------

    /** ¿El cliente puede cancelarlo? Solo mientras está Pendiente. Devuelve null o el motivo. */
    public static function validarCancelacion(array $contrato): ?string
    {
        return $contrato['estado'] === 'Pendiente'
            ? null
            : "Solo se puede cancelar una solicitud Pendiente. Su estado actual es {$contrato['estado']}.";
    }

    /** ¿Se puede aprobar o rechazar? Solo mientras está Pendiente. Devuelve null o el motivo. */
    public static function validarGestion(array $contrato): ?string
    {
        return $contrato['estado'] === 'Pendiente'
            ? null
            : "La solicitud {$contrato['numeroContrato']} ya no está Pendiente: su estado es {$contrato['estado']}.";
    }

    /**
     * ¿Se puede aprobar? Además de seguir Pendiente, su fecha de inicio no puede haber
     * pasado. (La disponibilidad de los equipos se revisa dentro de aprobar()).
     */
    public static function validarAprobacion(array $contrato): ?string
    {
        $error = self::validarGestion($contrato);
        if ($error === null && $contrato['fechaInicio'] < date('Y-m-d')) {
            $error = "No se puede aprobar {$contrato['numeroContrato']}: su fecha de inicio ("
                . fechaCorta($contrato['fechaInicio']) . ') ya pasó. Recházala y pide al cliente que solicite otras fechas.';
        }
        return $error;
    }

    /** Motivo del rechazo: obligatorio. Devuelve ['motivoRechazo' => 'mensaje']; vacío = válido. */
    public static function validarMotivoRechazo(string $motivo): array
    {
        if (!mb_check_encoding($motivo, 'UTF-8')) {
            return ['motivoRechazo' => 'El texto tiene caracteres no válidos. Escríbelo de nuevo.'];
        }
        if ($motivo === '') {
            return ['motivoRechazo' => 'Escribe el motivo del rechazo: el cliente lo verá en "Mis alquileres".'];
        }
        if (mb_strlen($motivo) > self::LARGO_MOTIVO) {
            return ['motivoRechazo' => 'El motivo no puede tener más de ' . self::LARGO_MOTIVO . ' caracteres.'];
        }
        return [];
    }

    /**
     * Pasos de la barra de progreso (Figma "Detalle - alquiler"), con los estados reales:
     * Solicitado (Pendiente) -> Aprobado -> En obra (Activo) -> Finalizado.
     * Rechazado y Cancelado no tienen progreso: devuelve [] y la vista muestra un aviso.
     *
     * @return array [['texto' => 'Aprobado', 'icono' => 'bi-check2', 'estado' => 'hecho' | 'actual' | 'pendiente'], ...]
     */
    public static function pasosProgreso(array $contrato): array
    {
        $pasos = [
            ['Solicitado', 'bi-send', 'Pendiente'],
            ['Aprobado', 'bi-check2-circle', 'Aprobado'],
            ['En obra', 'bi-truck', 'Activo'],
            ['Finalizado', 'bi-flag', 'Finalizado'],
        ];
        $actual = array_search($contrato['estado'], array_column($pasos, 2), true);
        if ($actual === false) {
            return [];
        }

        $resultado = [];
        foreach ($pasos as $posicion => [$texto, $icono]) {
            $estado = $posicion < $actual || $contrato['estado'] === 'Finalizado' ? 'hecho'
                : ($posicion === $actual ? 'actual' : 'pendiente');
            $resultado[] = ['texto' => $texto, 'icono' => $icono, 'estado' => $estado];
        }
        return $resultado;
    }

    /**
     * Importe guardado del contrato, con el mismo formato que Solicitud::importe(),
     * para reutilizar componentes/importe.php.
     */
    public static function importe(array $contrato): array
    {
        return [
            'lineas'   => self::lineas((int) $contrato['idContrato']),
            'subtotal' => $contrato['subtotal'],
            'deposito' => $contrato['depositoGarantia'],
            'total'    => $contrato['totalEstimado'],
        ];
    }

    /**
     * Cuánto del depósito se devuelve (solo para mostrar; no se guarda):
     * garantía = depósito − (penalización por atraso + daños cobrados al cliente), mínimo 0.
     * Si los cargos pasan el depósito, el cliente debe pagar el saldo.
     *
     * @param array $contrato Contrato::detalle() (depositoGarantia, penalizacion, cargosDanos)
     * @return array ['cargos', 'garantia', 'saldo']
     */
    public static function liquidacion(array $contrato): array
    {
        $cargos = round((float) $contrato['penalizacion'] + (float) $contrato['cargosDanos'], 2);
        $diferencia = round((float) $contrato['depositoGarantia'] - $cargos, 2);
        return ['cargos' => $cargos, 'garantia' => max(0, $diferencia), 'saldo' => max(0, -$diferencia)];
    }

    // ---------------------------------------------------------------------
    // Consultas
    // ---------------------------------------------------------------------

    /**
     * WHERE de buscar() y contar(). Las condiciones son textos fijos; los valores van
     * en $parametros. Alias: c (contrato), cl (cliente), u (usuario del cliente).
     */
    private static function condiciones(array $filtros): array
    {
        $filtros += self::FILTROS;
        $condiciones = [];
        $parametros = [];

        if ($filtros['idCliente'] > 0) {
            $condiciones[] = 'c.idCliente = ?';
            $parametros[] = $filtros['idCliente'];
        }
        if ($filtros['estado'] !== '') {
            $condiciones[] = 'c.estado = ?';
            $parametros[] = $filtros['estado'];
        }
        if (isset(self::PENDIENTES[$filtros['pendiente']])) {
            $condiciones[] = self::PENDIENTES[$filtros['pendiente']];
        }
        if ($filtros['numero'] !== '') {
            $condiciones[] = 'c.numeroContrato LIKE ?';
            $parametros[] = '%' . $filtros['numero'] . '%';
        }
        if ($filtros['cliente'] !== '') {
            $condiciones[] = "(cl.codigoCliente LIKE ? OR cl.razonSocial LIKE ? OR CONCAT(u.nombres, ' ', u.apellidos) LIKE ?)";
            $patron = '%' . $filtros['cliente'] . '%';
            array_push($parametros, $patron, $patron, $patron);
        }
        if ($filtros['equipo'] !== '') {
            // EXISTS: el contrato sale una sola vez aunque varios de sus equipos coincidan.
            $condiciones[] = 'EXISTS (SELECT 1 FROM detalleContrato dcf JOIN equipo ef ON ef.idEquipo = dcf.idEquipo
                                       WHERE dcf.idContrato = c.idContrato AND (ef.nombre LIKE ? OR ef.codigoInventario LIKE ?))';
            $patron = '%' . $filtros['equipo'] . '%';
            array_push($parametros, $patron, $patron);
        }
        // Período que se cruza con el rango: termina después de "desde" y empieza antes de "hasta".
        if ($filtros['desde'] !== '') {
            $condiciones[] = 'c.fechaFin >= ?';
            $parametros[] = $filtros['desde'];
        }
        if ($filtros['hasta'] !== '') {
            $condiciones[] = 'c.fechaInicio <= ?';
            $parametros[] = $filtros['hasta'];
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        return [$where, $parametros];
    }

    public static function contar(array $filtros): int
    {
        [$where, $parametros] = self::condiciones($filtros);
        $sentencia = Database::conexion()->prepare(
            "SELECT COUNT(*)
               FROM contrato c
               JOIN cliente cl ON cl.idCliente = c.idCliente
               JOIN usuario u ON u.idUsuario = cl.idUsuario
               $where"
        );
        $sentencia->execute($parametros);
        return (int) $sentencia->fetchColumn();
    }

    /**
     * Una página de contratos en UNA consulta: con el cliente, el nombre del primer
     * equipo (por orden alfabético) y cuántos equipos tiene.
     * GROUP BY c.idContrato basta: las demás columnas dependen de él (MySQL lo acepta).
     *
     * @param string $orden 'recientes' | 'antiguos'
     */
    public static function buscar(array $filtros, string $orden, int $limite, int $desplazamiento): array
    {
        [$where, $parametros] = self::condiciones($filtros);
        $sentencia = Database::conexion()->prepare(
            'SELECT c.idContrato, c.numeroContrato, c.estado, c.fechaSolicitud, c.fechaInicio, c.fechaFin,
                    c.destino, c.totalEstimado, DATEDIFF(c.fechaFin, c.fechaInicio) + 1 AS dias,
                    cl.codigoCliente, ' . Cliente::NOMBRE_SQL . " AS cliente,
                    MIN(e.nombre) AS primerEquipo, COUNT(dc.idDetalleContrato) AS equipos
               FROM contrato c
               JOIN cliente cl ON cl.idCliente = c.idCliente
               JOIN usuario u ON u.idUsuario = cl.idUsuario
               JOIN detalleContrato dc ON dc.idContrato = c.idContrato
               JOIN equipo e ON e.idEquipo = dc.idEquipo
               $where
              GROUP BY c.idContrato
              ORDER BY " . (self::ORDENES[$orden] ?? self::ORDENES['recientes']) . '
              LIMIT ? OFFSET ?'
        );
        $posicion = 1;
        foreach ($parametros as $valor) {
            $sentencia->bindValue($posicion++, $valor);
        }
        $sentencia->bindValue($posicion++, $limite, PDO::PARAM_INT);
        $sentencia->bindValue($posicion, $desplazamiento, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /**
     * Un contrato con su cliente, quién lo gestionó y quién recibió la primera entrega
     * (si ya hubo), o null si no existe. Con $idCliente, solo si es de ese cliente.
     */
    public static function detalle(int $idContrato, ?int $idCliente = null): ?array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT c.idContrato, c.numeroContrato, c.estado, c.fechaSolicitud, c.fechaInicio, c.fechaFin,
                    c.destino, c.observaciones, c.motivoRechazo, c.fechaAprobacion,
                    c.subtotal, c.depositoGarantia, c.totalEstimado, DATEDIFF(c.fechaFin, c.fechaInicio) + 1 AS dias,
                    c.idCliente, cl.codigoCliente, cl.tipoPersona, ' . Cliente::NOMBRE_SQL . ' AS cliente,
                    COALESCE(cl.documentoTributario, u.documentoIdentidad) AS documento, u.correo, u.telefono,
                    CONCAT(g.nombres, \' \', g.apellidos) AS gestionadoPor,
                    (SELECT en.nombreReceptor FROM entrega en WHERE en.idContrato = c.idContrato
                      ORDER BY en.fechaEntrega LIMIT 1) AS recibidoPor,
                    (SELECT MIN(en.fechaEntrega) FROM entrega en WHERE en.idContrato = c.idContrato) AS fechaEntrega,
                    (SELECT COALESCE(SUM(dv.penalizacionTotal), 0) FROM devolucion dv WHERE dv.idContrato = c.idContrato) AS penalizacion,
                    (SELECT COALESCE(SUM(d.costoEstimado), 0) FROM dano d
                       JOIN detalleDevolucion dd ON dd.idDetalleDevolucion = d.idDetalleDevolucion
                       JOIN devolucion dv ON dv.idDevolucion = dd.idDevolucion
                      WHERE dv.idContrato = c.idContrato AND d.cargoAlCliente = TRUE) AS cargosDanos
               FROM contrato c
               JOIN cliente cl ON cl.idCliente = c.idCliente
               JOIN usuario u ON u.idUsuario = cl.idUsuario
               LEFT JOIN usuario g ON g.idUsuario = c.idGestionadoPor
              WHERE c.idContrato = ?' . ($idCliente === null ? '' : ' AND c.idCliente = ?')
        );
        $sentencia->execute($idCliente === null ? [$idContrato] : [$idContrato, $idCliente]);
        $fila = $sentencia->fetch();
        return $fila === false ? null : $fila;
    }

    /**
     * Líneas del contrato con los nombres que usa componentes/importe.php
     * (precioDiario, dias, subtotal) y, por equipo, su entrega y su devolución:
     * fechaEntrega / fechaDevolucion (NULL = todavía no), horómetro de salida,
     * penalización, cuántos daños tuvo y si puede salir a obra (listoParaSalir:
     * activo, Disponible y sin mantenimiento abierto).
     */
    public static function lineas(int $idContrato): array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT dc.idDetalleContrato, e.idEquipo, e.codigoInventario, e.nombre, e.estado AS estadoEquipo,
                    dc.precioDiarioAplicado AS precioDiario, dc.diasFacturados AS dias, dc.subtotalLinea AS subtotal,
                    en.fechaEntrega, de.lecturaHorometro AS horometroSalida,
                    dv.fechaDevolucion, dd.penalizacion,
                    (SELECT COUNT(*) FROM dano d WHERE d.idDetalleDevolucion = dd.idDetalleDevolucion) AS danos,
                    (e.activo = TRUE AND e.estado = 'Disponible'
                     AND NOT EXISTS (SELECT 1 FROM mantenimiento m
                                      WHERE m.idEquipo = e.idEquipo AND m.estado NOT IN ('Finalizado', 'Cancelado'))) AS listoParaSalir
               FROM detalleContrato dc
               JOIN equipo e ON e.idEquipo = dc.idEquipo
               LEFT JOIN detalleEntrega de ON de.idDetalleContrato = dc.idDetalleContrato
               LEFT JOIN entrega en ON en.idEntrega = de.idEntrega
               LEFT JOIN detalleDevolucion dd ON dd.idDetalleContrato = dc.idDetalleContrato
               LEFT JOIN devolucion dv ON dv.idDevolucion = dd.idDevolucion
              WHERE dc.idContrato = ?
              ORDER BY e.nombre, e.codigoInventario"
        );
        $sentencia->execute([$idContrato]);
        return $sentencia->fetchAll();
    }

    // ---------------------------------------------------------------------
    // Cambios
    // ---------------------------------------------------------------------

    /**
     * Cancela la solicitud del cliente. El WHERE incluye idCliente y estado = 'Pendiente':
     * aunque dos pestañas lo intenten a la vez o el operador la apruebe en ese momento,
     * solo se cancela si sigue Pendiente. Devuelve true si se canceló.
     */
    public static function cancelar(int $idContrato, int $idCliente): bool
    {
        $sentencia = Database::conexion()->prepare(
            "UPDATE contrato SET estado = 'Cancelado' WHERE idContrato = ? AND idCliente = ? AND estado = 'Pendiente'"
        );
        $sentencia->execute([$idContrato, $idCliente]);
        return $sentencia->rowCount() === 1;
    }

    /**
     * Aprueba la solicitud (ya validada con validarAprobacion), en UNA transacción:
     *   1. UPDATE condicionado a estado = 'Pendiente'. Si otro operador la aprobó o
     *      rechazó primero, no cambia ninguna fila. Además deja la fila bloqueada hasta
     *      el commit: una segunda aprobación simultánea espera y luego no encuentra
     *      la solicitud Pendiente.
     *   2. Se vuelve a revisar cada equipo (Solicitud::conflictos, sin contar este
     *      contrato): pudo entrar a mantenimiento, darse de baja o quedar retenido por
     *      un alquiler vencido sin devolver. Si alguno ya no está libre, rollback.
     * Devuelve null si se aprobó, o el mensaje de por qué no.
     */
    public static function aprobar(array $contrato, int $idUsuario): ?string
    {
        $idContrato = (int) $contrato['idContrato'];
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $sentencia = $conexion->prepare(
                "UPDATE contrato SET estado = 'Aprobado', fechaAprobacion = CURRENT_TIMESTAMP, idGestionadoPor = ?
                  WHERE idContrato = ? AND estado = 'Pendiente'"
            );
            $sentencia->execute([$idUsuario, $idContrato]);
            if ($sentencia->rowCount() !== 1) {
                $conexion->rollBack();
                return self::mensajeYaGestionada($contrato);
            }

            $ocupados = Solicitud::conflictos(array_column(self::lineas($idContrato), 'idEquipo'),
                $contrato['fechaInicio'], $contrato['fechaFin'], $idContrato);
            if ($ocupados) {
                $conexion->rollBack();
                $verbo = count($ocupados) > 1 ? 'ya no están libres' : 'ya no está libre';
                return "No se puede aprobar {$contrato['numeroContrato']}: " . implode(', ', $ocupados) . " $verbo del "
                    . periodo($contrato['fechaInicio'], $contrato['fechaFin'])
                    . ' (está en mantenimiento, se dio de baja o tiene un alquiler vencido sin devolver). Recházala indicando el motivo.';
            }

            $conexion->commit();
            return null;
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Rechaza la solicitud con su motivo (ya validado). Mismo UPDATE condicionado que
     * aprobar(). Las fechas quedan libres solas: fn_conflictosEquipo no cuenta los
     * contratos Rechazados. Devuelve null si se rechazó, o el mensaje de por qué no.
     */
    public static function rechazar(array $contrato, int $idUsuario, string $motivo): ?string
    {
        $sentencia = Database::conexion()->prepare(
            "UPDATE contrato SET estado = 'Rechazado', motivoRechazo = ?, idGestionadoPor = ?
              WHERE idContrato = ? AND estado = 'Pendiente'"
        );
        $sentencia->execute([$motivo, $idUsuario, $contrato['idContrato']]);
        return $sentencia->rowCount() === 1 ? null : self::mensajeYaGestionada($contrato);
    }

    /** Otro usuario aprobó, rechazó (o el cliente canceló) entre que se abrió la página y se envió. */
    private static function mensajeYaGestionada(array $contrato): string
    {
        return "La solicitud {$contrato['numeroContrato']} ya no está Pendiente: otro usuario la gestionó primero.";
    }
}
