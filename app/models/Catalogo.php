<?php
/**
 * EquipRent - Consultas del catálogo que ve el cliente (vista v_catalogoEquipo).
 * Por ahora solo lo que usa la página de inicio del cliente; el resto llega
 * con el módulo de Catálogo.
 */
declare(strict_types=1);

class Catalogo
{
    /**
     * Categorías activas con más equipos que se pueden alquilar (los que no están fuera de servicio).
     */
    public static function categoriasPopulares(int $limite): array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT c.idCategoria, c.nombre, COUNT(e.idEquipo) AS equipos
               FROM categoria c
               JOIN equipo e ON e.idCategoria = c.idCategoria
              WHERE c.activa = TRUE
                AND e.activo = TRUE
                AND e.estado <> 'FueraServicio'
              GROUP BY c.idCategoria, c.nombre
              ORDER BY equipos DESC, c.nombre
              LIMIT ?"
        );
        $sentencia->bindValue(1, $limite, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /**
     * Un equipo por categoría (el primero registrado), para mostrar en "Equipos destacados".
     */
    public static function equiposDestacados(int $limite): array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT v.idEquipo, v.nombre, v.categoria, v.precioDiario, v.fotoPrincipal
               FROM v_catalogoEquipo v
              WHERE v.activo = TRUE
                AND v.estado <> 'FueraServicio'
                AND v.idEquipo = (SELECT MIN(v2.idEquipo)
                                    FROM v_catalogoEquipo v2
                                   WHERE v2.idCategoria = v.idCategoria
                                     AND v2.activo = TRUE
                                     AND v2.estado <> 'FueraServicio')
              ORDER BY v.categoria
              LIMIT ?"
        );
        $sentencia->bindValue(1, $limite, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /**
     * Inventario para el panel del administrador (los primeros $limite equipos por código).
     */
    public static function inventarioResumido(int $limite): array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT codigoInventario, nombre, categoria, precioDiario, estado
               FROM v_catalogoEquipo
              WHERE activo = TRUE
              ORDER BY codigoInventario
              LIMIT ?"
        );
        $sentencia->bindValue(1, $limite, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /** Categorías activas para listas desplegables. */
    public static function categoriasActivas(): array
    {
        $sentencia = Database::conexion()->query(
            'SELECT idCategoria, nombre FROM categoria WHERE activa = TRUE ORDER BY nombre'
        );
        return $sentencia->fetchAll();
    }
}
