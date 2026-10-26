<?php
/**
 * EquipRent - Modelo de la tabla usuario: inicio de sesión, validaciones comunes a toda
 * persona (las usa también Cliente) y contraseñas.
 */
declare(strict_types=1);

class Usuario
{
    /** Largo máximo de los datos de una persona, igual que en database/01_schema.sql. */
    private const LARGOS = [
        'nombres'            => 80,
        'apellidos'          => 80,
        'correo'             => 120,
        'telefono'           => 20,
        'documentoIdentidad' => 25,
        'direccion'          => 250,
    ];

    /** Mensajes del error 1062 según el UNIQUE que lo produjo (ver esValorDuplicado()). */
    public const DUPLICADOS = [
        'uq_usuario_correo'    => ['correo', 'Ya hay un usuario con ese correo.'],
        'uq_usuario_documento' => ['documentoIdentidad', 'Ya hay un usuario con ese documento de identidad.'],
    ];

    // ---------------------------------------------------------------------
    // Validaciones
    // ---------------------------------------------------------------------

    /**
     * Datos de una persona (tabla usuario): nombres y apellidos, documento de identidad
     * y correo obligatorios y únicos, teléfono con formato y largos máximos.
     * Devuelve ['campo' => 'mensaje']; vacío = todo bien.
     *
     * @param int $idUsuario el usuario que se edita (0 al crear), para no chocar consigo mismo
     */
    public static function validarPersona(array $datos, int $idUsuario): array
    {
        foreach ($datos as $valor) {
            if (is_string($valor) && !mb_check_encoding($valor, 'UTF-8')) {
                return ['nombres' => 'El texto tiene caracteres no válidos. Escríbelo de nuevo.'];
            }
        }
        $errores = [];
        foreach (self::LARGOS as $campo => $largo) {
            if (mb_strlen($datos[$campo]) > $largo) {
                $errores[$campo] = "No puede tener más de $largo caracteres.";
            }
        }
        if ($datos['nombres'] === '' && !isset($errores['nombres'])) {
            $errores['nombres'] = 'Escribe los nombres.';
        }
        if ($datos['apellidos'] === '' && !isset($errores['apellidos'])) {
            $errores['apellidos'] = 'Escribe los apellidos.';
        }

        if ($datos['documentoIdentidad'] === '') {
            $errores['documentoIdentidad'] = 'Escribe el documento de identidad (DUI o pasaporte).';
        } elseif (!isset($errores['documentoIdentidad']) && !preg_match('/^[0-9A-Za-z-]+$/', $datos['documentoIdentidad'])) {
            $errores['documentoIdentidad'] = 'Usa solo números, letras y guiones (por ejemplo 01234567-8).';
        } elseif (!isset($errores['documentoIdentidad']) && self::existe('documentoIdentidad', $datos['documentoIdentidad'], $idUsuario)) {
            $errores['documentoIdentidad'] = self::DUPLICADOS['uq_usuario_documento'][1];
        }

        // El correo es el usuario para iniciar sesión: obligatorio y único.
        if ($datos['correo'] === '') {
            $errores['correo'] = 'Escribe el correo: es el usuario para iniciar sesión.';
        } elseif (!isset($errores['correo']) && !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Escribe un correo válido, por ejemplo nombre@empresa.com.';
        } elseif (!isset($errores['correo']) && self::existe('correo', $datos['correo'], $idUsuario)) {
            $errores['correo'] = self::DUPLICADOS['uq_usuario_correo'][1];
        }

        if ($datos['telefono'] !== '' && !isset($errores['telefono']) && !self::esTelefono($datos['telefono'])) {
            $errores['telefono'] = 'El teléfono no es válido. Ejemplo: 2222-0000 o +503 7000-0000.';
        }
        return $errores;
    }

    public static function esTelefono(string $valor): bool
    {
        return (bool) preg_match('/^\+?[0-9][0-9 ()-]{6,18}$/', $valor);
    }

    /**
     * Contraseña elegida por el usuario: mínimo 8 caracteres, con letras y números,
     * y escrita igual dos veces. Devuelve ['clave' | 'confirmacion' => 'mensaje'].
     */
    public static function validarClave(string $clave, string $confirmacion): array
    {
        if (mb_strlen($clave) < 8 || !preg_match('/\pL/u', $clave) || !preg_match('/\d/', $clave)) {
            return ['clave' => 'La contraseña debe tener al menos 8 caracteres, con letras y números.'];
        }
        if (strlen($clave) > 72) {
            return ['clave' => 'La contraseña no puede tener más de 72 caracteres.']; // límite de bcrypt
        }
        return $clave === $confirmacion ? [] : ['confirmacion' => 'Las contraseñas no coinciden.'];
    }

    /**
     * Contraseña temporal legible para entregar a la persona: 10 caracteres sin los que
     * se confunden (0/O, 1/l/I), con mayúsculas, minúsculas y números.
     */
    public static function generarClaveTemporal(): string
    {
        $letras = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
        $numeros = '23456789';
        $todos = $letras . $numeros;
        $clave = $letras[random_int(0, 23)] . $letras[random_int(24, strlen($letras) - 1)] . $numeros[random_int(0, 7)];
        for ($i = 0; $i < 7; $i++) {
            $clave .= $todos[random_int(0, strlen($todos) - 1)];
        }
        return str_shuffle($clave);
    }

    /** ¿Otro usuario (distinto de $excepto) ya usa ese valor? Solo columnas fijas. */
    private static function existe(string $columna, string $valor, int $excepto): bool
    {
        $columnas = ['correo' => 'correo', 'documentoIdentidad' => 'documentoIdentidad'];
        $sentencia = Database::conexion()->prepare(
            "SELECT COUNT(*) FROM usuario WHERE {$columnas[$columna]} = ? AND idUsuario <> ?"
        );
        $sentencia->execute([$valor, $excepto]);
        return (int) $sentencia->fetchColumn() > 0;
    }

    // ---------------------------------------------------------------------
    // Inicio de sesión
    // ---------------------------------------------------------------------

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
