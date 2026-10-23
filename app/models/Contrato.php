<?php
/**
 * EquipRent - Modelo de contratos (tablas contrato + detalleContrato).
 *
 * Por ahora lo usa "Mis alquileres" del cliente (RF-08). Todas las consultas del
 * cliente filtran por idCliente: un cliente nunca ve ni cancela contratos ajenos.
 * El módulo de Operación lo ampliará (aprobar, rechazar, entregas...).
 */
declare(strict_types=1);

class Contrato
{
    /** Estados de la base, en el orden en que se muestran en el filtro. */
    public const ESTADOS = ['Pendiente', 'Aprobado', 'Activo', 'Finalizado', 'Rechazado', 'Cancelado'];

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

    // ---------------------------------------------------------------------
    // Consultas del cliente
    // ---------------------------------------------------------------------

    public static function contarDelCliente(int $idCliente, string $estado): int
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT COUNT(*) FROM contrato WHERE idCliente = ? AND (? = \'\' OR estado = ?)'
        );
        $sentencia->execute([$idCliente, $estado, $estado]);
        return (int) $sentencia->fetchColumn();
    }

    /**
     * Contratos del cliente, del más reciente al más antiguo, en UNA consulta: con el
     * nombre del primer equipo (por orden alfabético) y cuántos equipos tiene.
     *
     * @param string $estado '' = todos
     */
    public static function listarDelCliente(int $idCliente, string $estado, int $limite, int $desplazamiento): array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT c.idContrato, c.numeroContrato, c.estado, c.fechaInicio, c.fechaFin, c.totalEstimado,
                    MIN(e.nombre) AS primerEquipo, COUNT(dc.idDetalleContrato) AS equipos
               FROM contrato c
               JOIN detalleContrato dc ON dc.idContrato = c.idContrato
               JOIN equipo e ON e.idEquipo = dc.idEquipo
              WHERE c.idCliente = ? AND (? = '' OR c.estado = ?)
              GROUP BY c.idContrato, c.numeroContrato, c.estado, c.fechaInicio, c.fechaFin, c.totalEstimado
              ORDER BY c.fechaSolicitud DESC, c.idContrato DESC
              LIMIT ? OFFSET ?"
        );
        $sentencia->bindValue(1, $idCliente, PDO::PARAM_INT);
        $sentencia->bindValue(2, $estado);
        $sentencia->bindValue(3, $estado);
        $sentencia->bindValue(4, $limite, PDO::PARAM_INT);
        $sentencia->bindValue(5, $desplazamiento, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /**
     * Un contrato del cliente, con quién recibió la primera entrega (si ya hubo), o null
     * si no existe o es de otro cliente.
     */
    public static function detalleDelCliente(int $idContrato, int $idCliente): ?array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT c.idContrato, c.numeroContrato, c.estado, c.fechaSolicitud, c.fechaInicio, c.fechaFin,
                    c.destino, c.observaciones, c.motivoRechazo, c.fechaAprobacion,
                    c.subtotal, c.depositoGarantia, c.totalEstimado,
                    (SELECT en.nombreReceptor FROM entrega en WHERE en.idContrato = c.idContrato
                      ORDER BY en.fechaEntrega LIMIT 1) AS recibidoPor,
                    (SELECT MIN(en.fechaEntrega) FROM entrega en WHERE en.idContrato = c.idContrato) AS fechaEntrega
               FROM contrato c
              WHERE c.idContrato = ? AND c.idCliente = ?'
        );
        $sentencia->execute([$idContrato, $idCliente]);
        $fila = $sentencia->fetch();
        return $fila === false ? null : $fila;
    }

    /**
     * Líneas del contrato con los nombres que usa componentes/importe.php
     * (precioDiario, dias, subtotal).
     */
    public static function lineas(int $idContrato): array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT e.idEquipo, e.codigoInventario, e.nombre,
                    dc.precioDiarioAplicado AS precioDiario, dc.diasFacturados AS dias, dc.subtotalLinea AS subtotal
               FROM detalleContrato dc
               JOIN equipo e ON e.idEquipo = dc.idEquipo
              WHERE dc.idContrato = ?
              ORDER BY e.nombre, e.codigoInventario'
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
}
