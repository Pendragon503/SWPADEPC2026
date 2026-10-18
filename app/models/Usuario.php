<?php
/**
 * EquipRent - Modelo de la tabla usuario.
 */
declare(strict_types=1);

class Usuario
{
    /**
     * Usuario con su rol y, si es cliente, su idCliente. Null si el correo no existe.
     */
    public static function buscarPorCorreo(string $correo): ?array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT u.idUsuario, u.nombres, u.apellidos, u.correo, u.claveHash, u.activo,
                    r.nombre AS rol, r.activo AS rolActivo,
                    c.idCliente
               FROM usuario u
               JOIN rol r          ON r.idRol = u.idRol
               LEFT JOIN cliente c ON c.idUsuario = u.idUsuario
              WHERE u.correo = ?'
        );
        $sentencia->execute([$correo]);
        $fila = $sentencia->fetch();
        return $fila === false ? null : $fila;
    }

    public static function actualizarUltimoAcceso(int $idUsuario): void
    {
        $sentencia = Database::conexion()->prepare(
            'UPDATE usuario SET ultimoAcceso = CURRENT_TIMESTAMP WHERE idUsuario = ?'
        );
        $sentencia->execute([$idUsuario]);
    }

    public static function actualizarClaveHash(int $idUsuario, string $claveHash): void
    {
        $sentencia = Database::conexion()->prepare(
            'UPDATE usuario SET claveHash = ? WHERE idUsuario = ?'
        );
        $sentencia->execute([$claveHash, $idUsuario]);
    }
}
