<?php
/**
 * EquipRent - Reportes (RF-14): alquileres agrupados, historial de un equipo y los
 * reportes de Configuración → Ver reportes (con su historial en reporteGenerado).
 */
declare(strict_types=1);

class Reporte
{
    /** Tipos de "Ver reportes": clave (la de reporteGenerado.tipo) => título. */
    public const TIPOS = [
        'alquileres' => 'Alquileres',
        'inventario' => 'Inventario y disponibilidad',
        'fallas'     => 'Fallas y desperfectos',
        'salidas'    => 'Salidas y entregas',
    ];
    public const FORMATOS = ['PDF', 'Excel'];

    /** Generador: tipo y formato de la lista, y un rango de fechas válido. Devuelve ['campo' => 'mensaje']. */
    public static function validarGeneracion(array $datos): array
    {
        $errores = [];
        if (!isset(self::TIPOS[$datos['tipo']])) {
            $errores['tipo'] = 'Elige el tipo de reporte.';
        }
        if (!in_array($datos['formato'], self::FORMATOS, true)) {
            $errores['formato'] = 'Elige PDF o Excel.';
        }
        if (!esFecha($datos['desde']) || !esFecha($datos['hasta'])) {
            $errores['desde'] = 'Elige la fecha de inicio y la de fin.';
        } elseif ($datos['hasta'] < $datos['desde']) {
            $errores['hasta'] = 'La fecha de fin no puede ser anterior a la de inicio.';
        }
        return $errores;
    }

    /**
     * Rango de cada reporte rápido: inventario = corte al día de hoy, fallas = el mes
     * actual, salidas = la semana actual (lunes a domingo).
     */
    public static function rangosRapidos(): array
    {
        return [
            'inventario' => [date('Y-m-d'), date('Y-m-d')],
            'fallas'     => [date('Y-m-01'), date('Y-m-t')],
            'salidas'    => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))],
        ];
    }

    /** Números de las tres tarjetas de reportes rápidos, en UNA consulta. */
    public static function tarjetas(): array
    {
        $rangos = self::rangosRapidos();
        $sentencia = Database::conexion()->prepare(
            "SELECT (SELECT COUNT(*) FROM equipo WHERE activo = TRUE) AS equipos,
                    (SELECT COUNT(*) FROM equipo WHERE activo = TRUE AND estado = 'Disponible') AS disponibles,
                    (SELECT COUNT(*) FROM dano WHERE DATE(fechaRegistro) BETWEEN ? AND ?) AS danos,
                    (SELECT COUNT(*) FROM mantenimiento WHERE DATE(fechaIngreso) BETWEEN ? AND ?) AS mantenimientos,
                    (SELECT COUNT(*) FROM entrega en JOIN detalleEntrega de ON de.idEntrega = en.idEntrega
                      WHERE DATE(en.fechaEntrega) BETWEEN ? AND ?) AS salidas"
        );
        $sentencia->execute(array_merge($rangos['fallas'], $rangos['fallas'], $rangos['salidas']));
        return array_map('intval', $sentencia->fetch());
    }

    /**
     * Contenido de un reporte, el mismo para la página imprimible (PDF) y para el CSV:
     *   ['titulo', 'secciones' => [['titulo', 'columnas' => [[texto, formato], ...], 'filas' => [[...]], 'total' => fila|null]]]
     * formato de columna: 'texto' | 'numero' | 'dinero' | 'fecha' | 'estado' (lo usa la vista).
     */
    public static function generar(string $tipo, string $desde, string $hasta): array
    {
        $titulo = self::TIPOS[$tipo];
        switch ($tipo) {
            case 'alquileres':
                $resumen = self::resumen(['desde' => $desde, 'hasta' => $hasta, 'agrupar' => 'periodo', 'estado' => '']);
                $filas = array_map(function (array $f): array {
                    return [$f['grupo'], $f['contratos'], $f['dias'], $f['importe']];
                }, $resumen['filas']);
                $total = $resumen['total'] ? ['Total', $resumen['total']['contratos'], $resumen['total']['dias'], $resumen['total']['importe']] : null;
                return ['titulo' => $titulo, 'secciones' => [
                    self::seccion('Alquileres por mes (sin Rechazados ni Cancelados)',
                        [['Mes', 'texto'], ['Contratos', 'numero'], ['Días-equipo', 'numero'], ['Importe', 'dinero']], $filas, $total),
                ]];

            case 'inventario': // corte al día: no usa el rango
                $porEstado = self::consultar("SELECT estado, COUNT(*) FROM equipo WHERE activo = TRUE GROUP BY estado WITH ROLLUP");
                $total = array_pop($porEstado);
                return ['titulo' => $titulo, 'secciones' => [
                    self::seccion('Equipos activos por estado', [['Estado', 'estado'], ['Equipos', 'numero']], $porEstado, ['Total', $total[1] ?? 0]),
                    self::seccion('Equipos', [['Código', 'texto'], ['Equipo', 'texto'], ['Categoría', 'texto'], ['Estado', 'estado'], ['Tarifa diaria', 'dinero']],
                        self::consultar('SELECT v.codigoInventario, v.nombre, v.categoria, v.estado, v.precioDiario
                                           FROM v_catalogoEquipo v WHERE v.activo = TRUE ORDER BY v.categoria, v.codigoInventario')),
                ]];

            case 'fallas':
                $danos = self::consultar(
                    "SELECT DATE(d.fechaRegistro), e.codigoInventario, e.nombre, c.numeroContrato, d.descripcion, d.severidad,
                            d.costoEstimado, IF(d.cargoAlCliente, 'Sí', 'No'), d.estado
                       FROM dano d
                       JOIN detalleDevolucion dd ON dd.idDetalleDevolucion = d.idDetalleDevolucion
                       JOIN detalleContrato dc ON dc.idDetalleContrato = dd.idDetalleContrato
                       JOIN contrato c ON c.idContrato = dc.idContrato
                       JOIN equipo e ON e.idEquipo = dc.idEquipo
                      WHERE DATE(d.fechaRegistro) BETWEEN ? AND ?
                      ORDER BY d.fechaRegistro, d.idDano", [$desde, $hasta]);
                $ordenes = self::consultar(
                    "SELECT CONCAT('#', m.idMantenimiento), e.codigoInventario, e.nombre, m.tipo, m.descripcion,
                            DATE(m.fechaIngreso), DATE(m.fechaSalidaReal), m.costo, m.estado
                       FROM mantenimiento m JOIN equipo e ON e.idEquipo = m.idEquipo
                      WHERE DATE(m.fechaIngreso) BETWEEN ? AND ?
                      ORDER BY m.fechaIngreso, m.idMantenimiento", [$desde, $hasta]);
                return ['titulo' => $titulo, 'secciones' => [
                    self::seccion('Daños registrados en devoluciones', [['Fecha', 'fecha'], ['Código', 'texto'], ['Equipo', 'texto'], ['Contrato', 'texto'],
                        ['Daño', 'texto'], ['Severidad', 'texto'], ['Costo estimado', 'dinero'], ['Se cobra', 'texto'], ['Estado', 'estado']],
                        $danos, ['Total', '', '', '', count($danos) . ' daños', '', array_sum(array_column($danos, 6)), '', '']),
                    self::seccion('Órdenes de mantenimiento', [['Orden', 'texto'], ['Código', 'texto'], ['Equipo', 'texto'], ['Tipo', 'texto'],
                        ['Trabajo', 'texto'], ['Ingreso', 'fecha'], ['Salida', 'fecha'], ['Costo', 'dinero'], ['Estado', 'estado']],
                        $ordenes, ['Total', '', '', '', count($ordenes) . ' órdenes', '', '', array_sum(array_column($ordenes, 7)), '']),
                ]];

            default: // salidas
                $salidas = self::consultar(
                    'SELECT en.fechaEntrega, c.numeroContrato, ' . Cliente::NOMBRE_SQL . ', e.codigoInventario, e.nombre,
                            de.estadoFisico, de.nivelCombustible, de.lecturaHorometro, en.nombreReceptor
                       FROM entrega en
                       JOIN detalleEntrega de ON de.idEntrega = en.idEntrega
                       JOIN detalleContrato dc ON dc.idDetalleContrato = de.idDetalleContrato
                       JOIN contrato c ON c.idContrato = dc.idContrato
                       JOIN cliente cl ON cl.idCliente = c.idCliente
                       JOIN usuario u ON u.idUsuario = cl.idUsuario
                       JOIN equipo e ON e.idEquipo = dc.idEquipo
                      WHERE DATE(en.fechaEntrega) BETWEEN ? AND ?
                      ORDER BY en.fechaEntrega, de.idDetalleEntrega', [$desde, $hasta]);
                return ['titulo' => $titulo, 'secciones' => [
                    self::seccion('Equipos entregados', [['Fecha y hora', 'fecha'], ['Contrato', 'texto'], ['Cliente', 'texto'], ['Código', 'texto'],
                        ['Equipo', 'texto'], ['Estado físico', 'texto'], ['Combustible %', 'numero'], ['Horómetro', 'numero'], ['Recibió', 'texto']],
                        $salidas, ['Total', count($salidas) . ' equipos', '', '', '', '', '', '', '']),
                ]];
        }
    }

    /** Guarda en el historial que se generó un reporte. Devuelve el id. */
    public static function registrar(string $tipo, string $desde, string $hasta, string $formato, int $idUsuario): int
    {
        Database::conexion()->prepare(
            'INSERT INTO reporteGenerado (tipo, fechaInicio, fechaFin, formato, idUsuario) VALUES (?, ?, ?, ?, ?)'
        )->execute([$tipo, $desde, $hasta, $formato, $idUsuario]);
        return (int) Database::conexion()->lastInsertId();
    }

    /** Reportes generados en los últimos 30 días, del más reciente. */
    public static function historial(): array
    {
        return Database::conexion()->query(
            "SELECT r.idReporte, r.tipo, r.fechaInicio, r.fechaFin, r.formato, r.fechaGeneracion,
                    CONCAT(u.nombres, ' ', u.apellidos) AS usuario
               FROM reporteGenerado r JOIN usuario u ON u.idUsuario = r.idUsuario
              WHERE r.fechaGeneracion >= CURRENT_TIMESTAMP - INTERVAL 30 DAY
              ORDER BY r.fechaGeneracion DESC, r.idReporte DESC"
        )->fetchAll();
    }

    /** Una sección de reporte con su encabezado. */
    private static function seccion(string $titulo, array $columnas, array $filas, ?array $total = null): array
    {
        return ['titulo' => $titulo, 'columnas' => $columnas, 'filas' => $filas, 'total' => $total];
    }

    /** Filas como listas de valores (en el orden del SELECT). */
    private static function consultar(string $sql, array $parametros = []): array
    {
        $sentencia = Database::conexion()->prepare($sql);
        $sentencia->execute($parametros);
        return $sentencia->fetchAll(PDO::FETCH_NUM);
    }

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
