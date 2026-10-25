<?php
/**
 * EquipRent - Indicadores de los paneles (RF-13), con pocas consultas agrupadas.
 */
declare(strict_types=1);

class Indicador
{
    /**
     * Todos los conteos de los paneles en 2 consultas: equipos por estado y
     * contratos (por estado, atrasados, próximos a finalizar, entregas y devoluciones
     * pendientes) más las órdenes de mantenimiento abiertas.
     */
    public static function resumenGeneral(): array
    {
        $conexion = Database::conexion();
        $equipos = $conexion->query(
            "SELECT COUNT(*)                        AS equiposTotales,
                    SUM(estado = 'Disponible')      AS disponibles,
                    SUM(estado = 'Alquilado')       AS alquilados,
                    SUM(estado = 'Mantenimiento')   AS enMantenimiento,
                    SUM(estado = 'Revision')        AS enRevision,
                    SUM(estado = 'FueraServicio')   AS fueraServicio
               FROM equipo
              WHERE activo = TRUE"
        )->fetch();

        $contratos = $conexion->query(
            "SELECT SUM(c.estado = 'Pendiente')   AS pendientes,
                    SUM(c.estado = 'Aprobado')    AS aprobados,
                    SUM(c.estado = 'Activo')      AS alquileresActivos,
                    SUM(c.estado = 'Finalizado')  AS finalizados,
                    SUM(c.estado = 'Rechazado')   AS rechazados,
                    SUM(c.estado = 'Cancelado')   AS cancelados,
                    SUM(c.estado = 'Activo' AND c.fechaFin < CURDATE()) AS atrasados,
                    SUM(c.estado = 'Activo' AND c.fechaFin BETWEEN CURDATE() AND CURDATE() + INTERVAL 3 DAY) AS proximosAFinalizar,
                    SUM(" . Contrato::PENDIENTES['entrega'] . ")    AS entregasPendientes,
                    SUM(" . Contrato::PENDIENTES['devolucion'] . ") AS devolucionesPendientes,
                    (SELECT COUNT(*) FROM mantenimiento m WHERE m.estado NOT IN ('Finalizado', 'Cancelado')) AS mantenimientosAbiertos
               FROM contrato c"
        )->fetch();

        // SUM() de una tabla vacía es NULL y los conteos llegan como texto: se pasan a int.
        return array_map('intval', $equipos + $contratos);
    }

    /** Equipos entregados por día en los últimos 7 días (hoy incluido): ['AAAA-MM-DD' => cantidad]. */
    public static function entregasPorDia(): array
    {
        $porDia = Database::conexion()->query(
            'SELECT DATE(en.fechaEntrega) AS dia, COUNT(*) AS equipos
               FROM entrega en
               JOIN detalleEntrega de ON de.idEntrega = en.idEntrega
              WHERE en.fechaEntrega >= CURDATE() - INTERVAL 6 DAY
              GROUP BY dia'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $dias = [];
        for ($i = 6; $i >= 0; $i--) {
            $dia = date('Y-m-d', strtotime("-$i day"));
            $dias[$dia] = (int) ($porDia[$dia] ?? 0); // los días sin entregas quedan en 0
        }
        return $dias;
    }
}
