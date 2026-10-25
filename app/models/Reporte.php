<?php
/**
 * EquipRent - Reportes de alquileres (RF-14) e historial de un equipo.
 */
declare(strict_types=1);

class Reporte
{
    /**
     * Agrupaciones del reporte: clave => [título, expresión de grupo, etiqueta].
     * Alias: c (contrato), cl (cliente), u (usuario), e (equipo).
     */
    public const AGRUPACIONES = [
        'periodo' => ['Mes', "DATE_FORMAT(c.fechaInicio, '%Y-%m')", "DATE_FORMAT(c.fechaInicio, '%m/%Y')"],
        'cliente' => ['Cliente', 'c.idCliente', Cliente::NOMBRE_SQL],
        'equipo'  => ['Equipo', 'e.idEquipo', "CONCAT(e.codigoInventario, ' · ', e.nombre)"],
        'estado'  => ['Estado', 'c.estado', 'c.estado'],
    ];

    /**
     * Contratos cuya fecha de inicio cae en [desde, hasta], agrupados, en UNA consulta.
     * Días = días-equipo (suma de diasFacturados); importe = suma de los subtotales
     * (sin depósito). Sin estado elegido se excluyen los Rechazados y Cancelados.
     * WITH ROLLUP agrega la fila de totales.
     *
     * @param array $filtros ['desde', 'hasta', 'agrupar', 'estado']
     * @return array ['filas' => [['clave', 'grupo', 'contratos', 'dias', 'importe'], ...], 'total' => [...]]
     */
    public static function resumen(array $filtros): array
    {
        [, $clave, $etiqueta] = self::AGRUPACIONES[$filtros['agrupar']];
        $sentencia = Database::conexion()->prepare(
            // ANY_VALUE: la etiqueta depende de la clave del grupo (mismo cliente = mismo nombre).
            "SELECT $clave AS clave, ANY_VALUE($etiqueta) AS grupo, COUNT(DISTINCT c.idContrato) AS contratos,
                    SUM(dc.diasFacturados) AS dias, SUM(dc.subtotalLinea) AS importe
               FROM contrato c
               JOIN cliente cl ON cl.idCliente = c.idCliente
               JOIN usuario u ON u.idUsuario = cl.idUsuario
               JOIN detalleContrato dc ON dc.idContrato = c.idContrato
               JOIN equipo e ON e.idEquipo = dc.idEquipo
              WHERE c.fechaInicio BETWEEN ? AND ?
                AND " . ($filtros['estado'] === '' ? "c.estado NOT IN ('Rechazado', 'Cancelado')" : 'c.estado = ?') . "
              GROUP BY $clave WITH ROLLUP
              ORDER BY GROUPING($clave), " . ($filtros['agrupar'] === 'periodo' || $filtros['agrupar'] === 'estado' ? 'clave' : 'importe DESC')
        );
        $sentencia->execute(array_merge([$filtros['desde'], $filtros['hasta']], $filtros['estado'] === '' ? [] : [$filtros['estado']]));
        $filas = $sentencia->fetchAll();
        $total = array_pop($filas); // la fila de ROLLUP (clave NULL) queda al final
        return ['filas' => $filas, 'total' => $total];
    }

    /** Equipos para la lista del historial (todos, también los dados de baja). */
    public static function equipos(): array
    {
        return Database::conexion()->query('SELECT idEquipo, codigoInventario, nombre FROM equipo ORDER BY codigoInventario')->fetchAll();
    }

    /** Contratos de un equipo con su entrega y su devolución, del más reciente. */
    public static function contratosDeEquipo(int $idEquipo): array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT c.idContrato, c.numeroContrato, c.estado, c.fechaInicio, c.fechaFin, ' . Cliente::NOMBRE_SQL . ' AS cliente,
                    dc.subtotalLinea, en.fechaEntrega, en.nombreReceptor, dv.fechaDevolucion, dv.diasAtraso, dd.penalizacion
               FROM detalleContrato dc
               JOIN contrato c ON c.idContrato = dc.idContrato
               JOIN cliente cl ON cl.idCliente = c.idCliente
               JOIN usuario u ON u.idUsuario = cl.idUsuario
               LEFT JOIN detalleEntrega de ON de.idDetalleContrato = dc.idDetalleContrato
               LEFT JOIN entrega en ON en.idEntrega = de.idEntrega
               LEFT JOIN detalleDevolucion dd ON dd.idDetalleContrato = dc.idDetalleContrato
               LEFT JOIN devolucion dv ON dv.idDevolucion = dd.idDevolucion
              WHERE dc.idEquipo = ?
              ORDER BY c.fechaInicio DESC, c.idContrato DESC'
        );
        $sentencia->execute([$idEquipo]);
        return $sentencia->fetchAll();
    }
}
