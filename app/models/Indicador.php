<?php
/**
 * EquipRent - Indicadores de los paneles (RF-13).
 * Por ahora solo el resumen general; los demás llegan con el módulo de reportes.
 */
declare(strict_types=1);

class Indicador
{
    /**
     * Conteo de equipos por estado y de contratos activos, pendientes y atrasados.
     * Es la misma consulta de la prueba 6 de database/03_pruebas.sql.
     */
    public static function resumenGeneral(): array
    {
        $sentencia = Database::conexion()->query(
            "SELECT
               (SELECT COUNT(*) FROM equipo WHERE activo = TRUE)                                AS equiposTotales,
               (SELECT COUNT(*) FROM equipo WHERE activo = TRUE AND estado = 'Disponible')      AS disponibles,
               (SELECT COUNT(*) FROM equipo WHERE activo = TRUE AND estado = 'Alquilado')       AS alquilados,
               (SELECT COUNT(*) FROM equipo WHERE activo = TRUE AND estado = 'Mantenimiento')   AS enMantenimiento,
               (SELECT COUNT(*) FROM equipo WHERE activo = TRUE AND estado = 'FueraServicio')   AS fueraServicio,
               (SELECT COUNT(*) FROM contrato WHERE estado = 'Activo')                          AS alquileresActivos,
               (SELECT COUNT(*) FROM contrato WHERE estado = 'Pendiente')                       AS pendientes,
               (SELECT COUNT(*) FROM contrato WHERE estado = 'Activo' AND fechaFin < CURDATE()) AS atrasados"
        );

        // Los conteos llegan como texto o número según el driver; se pasan a int.
        return array_map('intval', $sentencia->fetch());
    }
}
