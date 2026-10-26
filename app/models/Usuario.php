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

    /** Roles del personal (los clientes se gestionan en Clientes). */
    public const ROLES_INTERNOS = ['Administrador', 'Operador'];

    /** Campos del modal de crear/editar usuario interno. */
    public const CAMPOS_FORMULARIO = ['idUsuario', 'nombres', 'apellidos', 'correo', 'telefono', 'documentoIdentidad', 'direccion', 'rol'];

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

    /**
     * Usuario interno (RF-02): datos de la persona y el rol. Al editar, no puedes quitarte
     * a ti mismo el rol de Administrador ni dejar el sistema sin un administrador activo.
     *
     * @param array|null $actual el usuario como está en la base (null al crear)
     */
    public static function validar(array $datos, ?array $actual): array
    {
        $errores = self::validarPersona($datos, $actual === null ? 0 : (int) $actual['idUsuario']);
        if (!in_array($datos['rol'], self::ROLES_INTERNOS, true)) {
            $errores['rol'] = 'Elige el rol: Administrador u Operador.';
        } elseif ($actual !== null && $actual['rol'] === 'Administrador' && $datos['rol'] !== 'Administrador') {
            if ((int) $actual['idUsuario'] === Auth::id()) {
                $errores['rol'] = 'No puedes quitarte a ti mismo el rol de Administrador.';
            } elseif ($actual['activo'] && self::administradoresActivos((int) $actual['idUsuario']) === 0) {
                $errores['rol'] = 'Debe quedar al menos un administrador activo.';
            }
        }
        return $errores;
    }

    /** ¿Se puede activar o desactivar? Devuelve null o el motivo. */
    public static function validarCambioActivo(array $usuario, bool $activar): ?string
    {
        if ((bool) $usuario['activo'] === $activar) {
            return $activar ? 'El usuario ya está activo.' : 'El usuario ya está desactivado.';
        }
        if (!$activar && (int) $usuario['idUsuario'] === Auth::id()) {
            return 'No puedes desactivarte a ti mismo.';
        }
        if (!$activar && $usuario['rol'] === 'Administrador' && self::administradoresActivos((int) $usuario['idUsuario']) === 0) {
            return 'No se puede desactivar: es el único administrador activo.';
        }
        return null;
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
     * Cambio de contraseña desde "Mi perfil": la actual debe ser correcta y la nueva
     * válida, confirmada y distinta. Devuelve ['claveActual' | 'clave' | 'confirmacion' => 'mensaje'].
     */
    public static function validarCambioClave(int $idUsuario, string $actual, string $nueva, string $confirmacion): array
    {
        $sentencia = Database::conexion()->prepare('SELECT claveHash FROM usuario WHERE idUsuario = ?');
        $sentencia->execute([$idUsuario]);
        if (!password_verify($actual, (string) $sentencia->fetchColumn())) {
            return ['claveActual' => 'La contraseña actual no es correcta.'];
        }
        if ($nueva === $actual) {
            return ['clave' => 'La nueva contraseña debe ser distinta de la actual.'];
        }
        return self::validarClave($nueva, $confirmacion);
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

    /** Administradores activos sin contar a $excepto. */
    private static function administradoresActivos(int $excepto): int
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT COUNT(*) FROM usuario u JOIN rol r ON r.idRol = u.idRol
              WHERE r.nombre = 'Administrador' AND u.activo = TRUE AND u.idUsuario <> ?"
        );
        $sentencia->execute([$excepto]);
        return (int) $sentencia->fetchColumn();
    }

    // ---------------------------------------------------------------------
    // Consultas y cambios de usuarios internos
    // ---------------------------------------------------------------------

    /** WHERE del listado: solo personal; texto (nombre, correo, documento), rol y activo ('1' | '0' | 'todos'). */
    private static function condiciones(array $filtros): array
    {
        $condiciones = ["r.nombre IN ('Administrador', 'Operador')"];
        $parametros = [];
        if ($filtros['buscar'] !== '') {
            $condiciones[] = "(CONCAT(u.nombres, ' ', u.apellidos) LIKE ? OR u.correo LIKE ? OR u.documentoIdentidad LIKE ?)";
            array_push($parametros, "%{$filtros['buscar']}%", "%{$filtros['buscar']}%", "%{$filtros['buscar']}%");
        }
        if ($filtros['rol'] !== '') {
            $condiciones[] = 'r.nombre = ?';
            $parametros[] = $filtros['rol'];
        }
        if ($filtros['activo'] !== 'todos') {
            $condiciones[] = 'u.activo = ?';
            $parametros[] = (int) $filtros['activo'];
        }
        return ['WHERE ' . implode(' AND ', $condiciones), $parametros];
    }

    public static function contar(array $filtros): int
    {
        [$where, $parametros] = self::condiciones($filtros);
        $sentencia = Database::conexion()->prepare("SELECT COUNT(*) FROM usuario u JOIN rol r ON r.idRol = u.idRol $where");
        $sentencia->execute($parametros);
        return (int) $sentencia->fetchColumn();
    }

    public static function listar(array $filtros, int $limite, int $desplazamiento): array
    {
        [$where, $parametros] = self::condiciones($filtros);
        $sentencia = Database::conexion()->prepare(
            'SELECT u.idUsuario, u.nombres, u.apellidos, u.correo, u.telefono, u.documentoIdentidad, u.direccion,
                    u.activo, u.ultimoAcceso, r.nombre AS rol
               FROM usuario u JOIN rol r ON r.idRol = u.idRol
               ' . $where . '
              ORDER BY r.nombre, u.nombres, u.apellidos
              LIMIT ' . $limite . ' OFFSET ' . $desplazamiento
        );
        $sentencia->execute($parametros);
        return $sentencia->fetchAll();
    }

    /** Usuario con su rol (cualquier rol), o null. */
    public static function buscarPorId(int $idUsuario): ?array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT u.idUsuario, u.nombres, u.apellidos, u.correo, u.telefono, u.documentoIdentidad, u.direccion,
                    u.activo, u.ultimoAcceso, u.fechaRegistro, r.nombre AS rol
               FROM usuario u JOIN rol r ON r.idRol = u.idRol
              WHERE u.idUsuario = ?'
        );
        $sentencia->execute([$idUsuario]);
        return $sentencia->fetch() ?: null;
    }

    /** Valores para llenar el modal al editar (data-campos): los NULL pasan a texto vacío. */
    public static function camposFormulario(array $usuario): array
    {
        $campos = [];
        foreach (self::CAMPOS_FORMULARIO as $campo) {
            $campos[$campo] = (string) ($usuario[$campo] ?? '');
        }
        return $campos;
    }

    /** Crea un usuario interno con su contraseña temporal (guardada con password_hash). Devuelve el id. */
    public static function crear(array $datos, string $claveTemporal): int
    {
        Database::conexion()->prepare(
            'INSERT INTO usuario (nombres, apellidos, correo, telefono, documentoIdentidad, direccion, claveHash, idRol)
             VALUES (?, ?, ?, ?, ?, ?, ?, (SELECT idRol FROM rol WHERE nombre = ?))'
        )->execute(array_merge(self::valores($datos), [password_hash($claveTemporal, PASSWORD_DEFAULT), $datos['rol']]));
        return (int) Database::conexion()->lastInsertId();
    }

    /** Actualiza los datos de la persona y su rol (la contraseña no se toca). */
    public static function actualizar(int $idUsuario, array $datos): void
    {
        Database::conexion()->prepare(
            'UPDATE usuario SET nombres = ?, apellidos = ?, correo = ?, telefono = ?, documentoIdentidad = ?, direccion = ?,
                                idRol = (SELECT idRol FROM rol WHERE nombre = ?)
              WHERE idUsuario = ?'
        )->execute(array_merge(self::valores($datos), [$datos['rol'], $idUsuario]));
    }

    public static function cambiarActivo(int $idUsuario, bool $activar): void
    {
        Database::conexion()->prepare('UPDATE usuario SET activo = ? WHERE idUsuario = ?')->execute([$activar ? 1 : 0, $idUsuario]);
    }

    /** Datos de la persona en el orden de los INSERT/UPDATE; los opcionales vacíos van como NULL. */
    private static function valores(array $datos): array
    {
        $nulo = function (string $valor): ?string {
            return $valor === '' ? null : $valor;
        };
        return [$datos['nombres'], $datos['apellidos'], $datos['correo'], $nulo($datos['telefono']),
            $datos['documentoIdentidad'], $nulo($datos['direccion'])];
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
