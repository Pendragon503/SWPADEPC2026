<?php
/**
 * EquipRent - Modelo de clientes (RF-03): tablas usuario + cliente.
 *
 * Cada cliente es un usuario con rol Cliente (para iniciar sesión) más una fila
 * en cliente con sus datos comerciales. Se crean juntos, en una transacción.
 *
 * Igual que Categoria y Equipo: aquí van las validaciones y las reglas de negocio;
 * el controlador solo lee los datos y decide qué mostrar.
 */
declare(strict_types=1);

class Cliente
{
    /** Tipos de persona de la base => texto para el usuario. */
    public const TIPOS = [
        'Natural'  => 'Persona natural',
        'Juridica' => 'Persona jurídica',
    ];

    /** Campos del modal de crear/editar (los "name" del formulario). */
    public const CAMPOS_FORMULARIO = [
        'idCliente', 'nombres', 'apellidos', 'correo', 'telefono', 'documentoIdentidad', 'direccion',
        'tipoPersona', 'razonSocial', 'documentoTributario', 'contactoAlterno', 'telefonoAlterno', 'observaciones',
    ];

    /** Largo máximo de los campos propios del cliente (los de la persona están en Usuario). */
    private const LARGOS = [
        'razonSocial'         => 150,
        'documentoTributario' => 25,
        'contactoAlterno'     => 120,
        'telefonoAlterno'     => 20,
        'observaciones'       => 500,
    ];

    /**
     * nombreVisible() en SQL, para las consultas de otros módulos (contratos...):
     * la razón social si es jurídica; si no, nombres y apellidos.
     * Usa los alias "cl" (cliente) y "u" (usuario).
     */
    public const NOMBRE_SQL = "CASE WHEN cl.tipoPersona = 'Juridica' AND cl.razonSocial <> ''
                                    THEN cl.razonSocial ELSE CONCAT(u.nombres, ' ', u.apellidos) END";

    /** Mensajes del error 1062 según el UNIQUE que lo produjo (ver esValorDuplicado()). */
    public const DUPLICADOS = Usuario::DUPLICADOS + [
        'uq_cliente_doctributario' => ['documentoTributario', 'Ya hay un cliente con ese documento tributario.'],
    ];

    // ---------------------------------------------------------------------
    // Validaciones y reglas de negocio
    // ---------------------------------------------------------------------

    /**
     * Valida el formulario (RF-03). Devuelve ['campo' => 'mensaje']; vacío = todo bien.
     *
     * @param array      $datos  campos del formulario (ver GestionClienteController::leerFormulario)
     * @param array|null $actual el cliente como está en la base al editar; null al crear
     */
    public static function validar(array $datos, ?array $actual): array
    {
        $idCliente = $actual === null ? 0 : (int) $actual['idCliente'];

        // Persona (o contacto, si es jurídica): nombres, documento, correo y teléfono (Usuario).
        $errores = Usuario::validarPersona($datos, $actual === null ? 0 : (int) $actual['idUsuario']);
        foreach (self::LARGOS as $campo => $largo) {
            if (mb_strlen($datos[$campo]) > $largo) {
                $errores[$campo] = "No puede tener más de $largo caracteres.";
            }
        }

        // Tipo de persona. Si es jurídica, la razón social y el documento tributario son obligatorios.
        if (!isset(self::TIPOS[$datos['tipoPersona']])) {
            $errores['tipoPersona'] = 'Elige si es persona natural o jurídica.';
        } elseif ($datos['tipoPersona'] === 'Juridica') {
            if ($datos['razonSocial'] === '') {
                $errores['razonSocial'] = 'Escribe la razón social de la empresa.';
            }
            if ($datos['documentoTributario'] === '') {
                $errores['documentoTributario'] = 'Escribe el documento tributario (NIT) de la empresa.';
            }
        }

        $nit = $datos['documentoTributario'];
        if ($nit !== '' && !isset($errores['documentoTributario'])) {
            if (!preg_match('/^[0-9A-Za-z-]+$/', $nit)) {
                $errores['documentoTributario'] = 'Usa solo números, letras y guiones (por ejemplo 0614-150820-102-1).';
            } elseif (self::existeTributario($nit, $idCliente)) {
                $errores['documentoTributario'] = self::DUPLICADOS['uq_cliente_doctributario'][1];
            }
        }

        // El correo (medio de contacto de RF-03) ya lo validó Usuario; aquí, el teléfono alterno.
        if ($datos['telefonoAlterno'] !== '' && !isset($errores['telefonoAlterno']) && !Usuario::esTelefono($datos['telefonoAlterno'])) {
            $errores['telefonoAlterno'] = 'El teléfono alterno no es válido. Ejemplo: 2222-0000 o +503 7000-0000.';
        }
        return $errores;
    }

    /**
     * ¿Se puede activar o desactivar? Devuelve null si sí, o el mensaje que explica por qué no.
     * REGLA: no se desactiva un cliente con un contrato Pendiente, Aprobado o Activo.
     */
    public static function validarCambioActivo(array $cliente, bool $activar): ?string
    {
        if ($activar) {
            return $cliente['activo'] ? 'El cliente ya está activo.' : null;
        }
        if (!$cliente['activo']) {
            return 'El cliente ya está desactivado.';
        }

        $contrato = self::contratoAbierto((int) $cliente['idCliente']);
        if ($contrato !== null) {
            return "No se puede desactivar: el cliente tiene el contrato {$contrato['numeroContrato']} "
                . "({$contrato['estado']}). Espera a que termine o cancélalo primero.";
        }
        return null;
    }

    /** Primer contrato Pendiente, Aprobado o Activo del cliente, o null. */
    public static function contratoAbierto(int $idCliente): ?array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT numeroContrato, estado
               FROM contrato
              WHERE idCliente = ? AND estado IN ('Pendiente', 'Aprobado', 'Activo')
              ORDER BY fechaInicio
              LIMIT 1"
        );
        $sentencia->execute([$idCliente]);
        $fila = $sentencia->fetch();
        return $fila === false ? null : $fila;
    }

    // ---------------------------------------------------------------------
    // Consultas del listado
    // ---------------------------------------------------------------------

    /**
     * WHERE del listado. Las condiciones son textos fijos; los valores van en $parametros.
     *
     * @param array $filtros ['buscar', 'tipoPersona' => '' | 'Natural' | 'Juridica', 'activo' => '1' | '0' | 'todos']
     */
    private static function condicionesListado(array $filtros): array
    {
        $condiciones = [];
        $parametros = [];

        if ($filtros['buscar'] !== '') {
            $condiciones[] = "(c.codigoCliente LIKE ? OR CONCAT(u.nombres, ' ', u.apellidos) LIKE ?
                               OR c.razonSocial LIKE ? OR u.documentoIdentidad LIKE ?
                               OR c.documentoTributario LIKE ? OR u.correo LIKE ?)";
            $patron = '%' . $filtros['buscar'] . '%';
            array_push($parametros, $patron, $patron, $patron, $patron, $patron, $patron);
        }
        if ($filtros['tipoPersona'] !== '') {
            $condiciones[] = 'c.tipoPersona = ?';
            $parametros[] = $filtros['tipoPersona'];
        }
        if ($filtros['activo'] === '1') {
            $condiciones[] = 'u.activo = TRUE';
        } elseif ($filtros['activo'] === '0') {
            $condiciones[] = 'u.activo = FALSE';
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        return [$where, $parametros];
    }

    public static function contar(array $filtros): int
    {
        [$where, $parametros] = self::condicionesListado($filtros);
        $sentencia = Database::conexion()->prepare(
            "SELECT COUNT(*) FROM cliente c JOIN usuario u ON u.idUsuario = c.idUsuario $where"
        );
        $sentencia->execute($parametros);
        return (int) $sentencia->fetchColumn();
    }

    public static function listar(array $filtros, int $limite, int $desplazamiento): array
    {
        [$where, $parametros] = self::condicionesListado($filtros);
        $sentencia = Database::conexion()->prepare(
            "SELECT " . self::COLUMNAS . "
               FROM cliente c
               JOIN usuario u ON u.idUsuario = c.idUsuario
               $where
              ORDER BY c.codigoCliente
              LIMIT ? OFFSET ?"
        );
        $posicion = 1;
        foreach ($parametros as $valor) {
            $sentencia->bindValue($posicion++, $valor);
        }
        $sentencia->bindValue($posicion++, $limite, PDO::PARAM_INT);
        $sentencia->bindValue($posicion, $desplazamiento, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /** Columnas que se leen de un cliente (cliente c + usuario u). */
    private const COLUMNAS = 'c.idCliente, c.codigoCliente, c.tipoPersona, c.documentoTributario, c.razonSocial,
                              c.contactoAlterno, c.telefonoAlterno, c.observaciones, c.fechaRegistro,
                              u.idUsuario, u.nombres, u.apellidos, u.correo, u.telefono,
                              u.documentoIdentidad, u.direccion, u.activo, u.ultimoAcceso';

    public static function buscarPorId(int $idCliente): ?array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM cliente c JOIN usuario u ON u.idUsuario = c.idUsuario WHERE c.idCliente = ?'
        );
        $sentencia->execute([$idCliente]);
        $fila = $sentencia->fetch();
        return $fila === false ? null : $fila;
    }

    /** Clientes activos para una lista desplegable (registro de alquiler): id, código y nombre. */
    public static function listarActivos(): array
    {
        return Database::conexion()->query(
            'SELECT cl.idCliente, cl.codigoCliente, ' . self::NOMBRE_SQL . ' AS nombre
               FROM cliente cl
               JOIN usuario u ON u.idUsuario = cl.idUsuario
              WHERE u.activo = TRUE
              ORDER BY nombre'
        )->fetchAll();
    }

    /**
     * Valores para llenar el modal al editar (data-campos): los NULL pasan a texto vacío.
     */
    public static function camposFormulario(array $cliente): array
    {
        $campos = [];
        foreach (self::CAMPOS_FORMULARIO as $campo) {
            $campos[$campo] = (string) ($cliente[$campo] ?? '');
        }
        return $campos;
    }

    /** Nombre para mostrar: la razón social si es jurídica; si no, nombres y apellidos. */
    public static function nombreVisible(array $cliente): string
    {
        if ($cliente['tipoPersona'] === 'Juridica' && $cliente['razonSocial'] !== null && $cliente['razonSocial'] !== '') {
            return $cliente['razonSocial'];
        }
        return trim($cliente['nombres'] . ' ' . $cliente['apellidos']);
    }

    // ---------------------------------------------------------------------
    // Cambios
    // ---------------------------------------------------------------------

    /**
     * Registra el cliente en UNA transacción:
     *   1. usuario con rol Cliente y la contraseña temporal (guardada con password_hash)
     *   2. fila en cliente
     *   3. codigoCliente = CLI-000001 a partir del id que asignó la base
     * Si algo falla, rollback: no queda un usuario sin cliente ni un cliente sin código.
     *
     * @return array ['idCliente' => int, 'codigoCliente' => string]
     */
    public static function crear(array $datos, string $claveTemporal): array
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $conexion->prepare(
                "INSERT INTO usuario (nombres, apellidos, correo, claveHash, telefono, documentoIdentidad, direccion, idRol)
                 VALUES (?, ?, ?, ?, ?, ?, ?, (SELECT idRol FROM rol WHERE nombre = 'Cliente'))"
            )->execute([
                $datos['nombres'],
                $datos['apellidos'],
                $datos['correo'],
                password_hash($claveTemporal, PASSWORD_DEFAULT),
                self::nuloSiVacio($datos['telefono']),
                $datos['documentoIdentidad'],
                self::nuloSiVacio($datos['direccion']),
            ]);
            $idUsuario = (int) $conexion->lastInsertId();

            $conexion->prepare(
                'INSERT INTO cliente (tipoPersona, documentoTributario, razonSocial, contactoAlterno, telefonoAlterno, observaciones, idUsuario)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute(array_merge(self::valoresCliente($datos), [$idUsuario]));
            $idCliente = (int) $conexion->lastInsertId();

            // El código se arma con el id que asignó la base, dentro de la misma transacción.
            $codigo = sprintf('CLI-%06d', $idCliente);
            $conexion->prepare('UPDATE cliente SET codigoCliente = ? WHERE idCliente = ?')->execute([$codigo, $idCliente]);

            $conexion->commit();
            return ['idCliente' => $idCliente, 'codigoCliente' => $codigo];
        } catch (Throwable $e) {
            $conexion->rollBack();
            throw $e;
        }
    }

    /** Actualiza usuario y cliente en una transacción (la contraseña y el código no se tocan). */
    public static function actualizar(array $actual, array $datos): void
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $conexion->prepare(
                'UPDATE usuario
                    SET nombres = ?, apellidos = ?, correo = ?, telefono = ?, documentoIdentidad = ?, direccion = ?
                  WHERE idUsuario = ?'
            )->execute([
                $datos['nombres'],
                $datos['apellidos'],
                $datos['correo'],
                self::nuloSiVacio($datos['telefono']),
                $datos['documentoIdentidad'],
                self::nuloSiVacio($datos['direccion']),
                $actual['idUsuario'],
            ]);
            $conexion->prepare(
                'UPDATE cliente
                    SET tipoPersona = ?, documentoTributario = ?, razonSocial = ?, contactoAlterno = ?,
                        telefonoAlterno = ?, observaciones = ?
                  WHERE idCliente = ?'
            )->execute(array_merge(self::valoresCliente($datos), [$actual['idCliente']]));
            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollBack();
            throw $e;
        }
    }

    /** Activa o desactiva el usuario del cliente (no se borra nada). */
    public static function cambiarActivo(array $cliente, bool $activar): void
    {
        Database::conexion()->prepare('UPDATE usuario SET activo = ? WHERE idUsuario = ?')
            ->execute([$activar ? 1 : 0, $cliente['idUsuario']]);
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    private static function existeTributario(string $nit, int $excepto): bool
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT COUNT(*) FROM cliente WHERE documentoTributario = ? AND idCliente <> ?'
        );
        $sentencia->execute([$nit, $excepto]);
        return (int) $sentencia->fetchColumn() > 0;
    }

    /** Valores de la tabla cliente en el orden de los INSERT/UPDATE. Natural: sin razón social. */
    private static function valoresCliente(array $datos): array
    {
        $esJuridica = $datos['tipoPersona'] === 'Juridica';
        return [
            $datos['tipoPersona'],
            self::nuloSiVacio($datos['documentoTributario']),
            $esJuridica ? $datos['razonSocial'] : null,
            self::nuloSiVacio($datos['contactoAlterno']),
            self::nuloSiVacio($datos['telefonoAlterno']),
            self::nuloSiVacio($datos['observaciones']),
        ];
    }

    private static function nuloSiVacio(string $valor): ?string
    {
        return $valor === '' ? null : $valor;
    }
}
