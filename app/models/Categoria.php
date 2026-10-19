<?php
/**
 * EquipRent - Modelo de la tabla categoria.
 *
 * MÓDULO DE EJEMPLO: copiarlo para crear otros modelos.
 *
 * Reglas que se repiten en todos los modelos:
 *  - Toda consulta que recibe datos usa una sentencia preparada (prepare + execute)
 *    con "?" en lugar de pegar el valor dentro del SQL. Así un texto como
 *    ' OR 1=1 -- nunca se ejecuta como SQL (inyección SQL).
 *  - Los nombres de tablas y columnas se escriben EXACTOS como en
 *    database/01_schema.sql (idCategoria, no idcategoria).
 *  - El modelo solo habla con la base de datos: no lee $_POST ni imprime HTML.
 */
declare(strict_types=1);

class Categoria
{
    /**
     * Cuántas categorías coinciden con la búsqueda (para calcular las páginas).
     */
    public static function contar(string $buscar): int
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT COUNT(*)
               FROM categoria
              WHERE nombre LIKE ? OR descripcion LIKE ?'
        );
        // '%texto%' = que contenga el texto en cualquier parte.
        $patron = '%' . $buscar . '%';
        $sentencia->execute([$patron, $patron]);
        return (int) $sentencia->fetchColumn();
    }

    /**
     * Una página del listado, con el número de equipos activos de cada categoría.
     *
     * @param int $limite         cuántas filas trae (tamaño de la página)
     * @param int $desplazamiento cuántas filas se salta ((página - 1) * tamaño)
     */
    public static function listar(string $buscar, int $limite, int $desplazamiento): array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT c.idCategoria, c.nombre, c.descripcion, c.activa,
                    COUNT(e.idEquipo) AS equiposActivos
               FROM categoria c
               LEFT JOIN equipo e ON e.idCategoria = c.idCategoria AND e.activo = TRUE
              WHERE c.nombre LIKE ? OR c.descripcion LIKE ?
              GROUP BY c.idCategoria, c.nombre, c.descripcion, c.activa
              ORDER BY c.nombre
              LIMIT ? OFFSET ?'
        );
        $patron = '%' . $buscar . '%';
        // LIMIT y OFFSET deben ir como enteros: por eso bindValue con PDO::PARAM_INT.
        $sentencia->bindValue(1, $patron);
        $sentencia->bindValue(2, $patron);
        $sentencia->bindValue(3, $limite, PDO::PARAM_INT);
        $sentencia->bindValue(4, $desplazamiento, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /** Una categoría por su id, o null si no existe. */
    public static function buscarPorId(int $idCategoria): ?array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT idCategoria, nombre, descripcion, activa FROM categoria WHERE idCategoria = ?'
        );
        $sentencia->execute([$idCategoria]);
        $fila = $sentencia->fetch();
        return $fila === false ? null : $fila;
    }

    /**
     * ¿Ya hay otra categoría con ese nombre?
     * La base usa utf8mb4_spanish_ci, que no distingue mayúsculas ni tildes:
     * "Excavadoras", "excavadoras" y "Excavadóras" cuentan como el mismo nombre.
     *
     * @param int $excepto id que no se compara (la misma categoría al editar; 0 al crear)
     */
    public static function existeNombre(string $nombre, int $excepto): bool
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT COUNT(*) FROM categoria WHERE nombre = ? AND idCategoria <> ?'
        );
        $sentencia->execute([$nombre, $excepto]);
        return (int) $sentencia->fetchColumn() > 0;
    }

    /** Crea una categoría activa y devuelve su id. */
    public static function crear(string $nombre, ?string $descripcion): int
    {
        $conexion = Database::conexion();
        $sentencia = $conexion->prepare(
            'INSERT INTO categoria (nombre, descripcion, activa) VALUES (?, ?, TRUE)'
        );
        $sentencia->execute([$nombre, $descripcion]);
        return (int) $conexion->lastInsertId();
    }

    public static function actualizar(int $idCategoria, string $nombre, ?string $descripcion): void
    {
        $sentencia = Database::conexion()->prepare(
            'UPDATE categoria SET nombre = ?, descripcion = ? WHERE idCategoria = ?'
        );
        $sentencia->execute([$nombre, $descripcion, $idCategoria]);
    }

    /** Equipos activos que pertenecen a la categoría. */
    public static function contarEquiposActivos(int $idCategoria): int
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT COUNT(*) FROM equipo WHERE idCategoria = ? AND activo = TRUE'
        );
        $sentencia->execute([$idCategoria]);
        return (int) $sentencia->fetchColumn();
    }

    /**
     * Activa o desactiva. Los registros no se borran: se desactivan, para no
     * perder el historial (equipos, contratos y reportes que los usan).
     */
    public static function cambiarActiva(int $idCategoria, bool $activa): void
    {
        $sentencia = Database::conexion()->prepare(
            'UPDATE categoria SET activa = ? WHERE idCategoria = ?'
        );
        $sentencia->execute([$activa ? 1 : 0, $idCategoria]);
    }
}
