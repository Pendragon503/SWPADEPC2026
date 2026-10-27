<?php
/**
 * EquipRent - Modelo de la tabla equipo (RF-04).
 *
 * Igual que Categoria: valida (validar...), aplica las reglas de negocio y hace
 * las consultas. El controlador solo lee los datos y decide qué mostrar.
 *
 * Bitácora: la tabla equipo tiene triggers que anotan en bitacoraEstadoEquipo
 * cada alta y cada cambio de estado, con @app_idUsuario (lo pone Database al
 * conectar, con el usuario en sesión) y @app_motivo (lo pone este modelo justo
 * antes del INSERT o UPDATE, y lo vuelve a NULL después).
 */
declare(strict_types=1);

class Equipo
{
    /** Todos los estados de la base: valor => texto para el usuario. */
    public const ESTADOS = [
        'Disponible'    => 'Disponible',
        'Alquilado'     => 'Alquilado',
        'Revision'      => 'En revisión',
        'Mantenimiento' => 'Mantenimiento',
        'FueraServicio' => 'Fuera de servicio',
    ];

    /** Estados que el administrador puede elegir a mano. "Alquilado" lo pone el módulo de Operación. */
    public const ESTADOS_MANUALES = ['Disponible', 'Revision', 'Mantenimiento', 'FueraServicio'];

    /** Motivos de baja (desactivación). "Otro" exige escribir el detalle. */
    public const MOTIVOS_BAJA = ['Retiro definitivo', 'Venta', 'Robo o pérdida', 'Daño irreparable', 'Otro'];

    public const ERROR_CODIGO_REPETIDO = 'Ya existe un equipo con ese código de inventario.';
    public const ERROR_SERIE_REPETIDA = 'Ya existe un equipo con ese número de serie.';

    /** Largo máximo que se acepta en los textos largos (columnas TEXT). */
    private const MAX_TEXTO_LARGO = 2000;

    /** Unidades que se registran a la vez en el modal "Registrar equipo" (campo Cantidad). */
    public const MAXIMO_UNIDADES = 20;

    /** Campos del modal de crear/editar (los "name" del formulario). */
    public const CAMPOS_FORMULARIO = [
        'idEquipo', 'codigoInventario', 'numeroSerie', 'nombre', 'idCategoria', 'estadoActual',
        'marca', 'modelo', 'fechaAdquisicion', 'valorReferencia',
        'descripcion', 'caracteristicasTecnicas', 'condicionesUso',
    ];

    /**
     * Valores para llenar el modal al editar (data-campos del botón "Editar"):
     * los NULL de la base pasan a texto vacío.
     */
    public static function camposFormulario(array $equipo): array
    {
        $campos = [];
        foreach (self::CAMPOS_FORMULARIO as $campo) {
            $campos[$campo] = (string) ($equipo[$campo] ?? '');
        }
        $campos['estadoActual'] = self::ESTADOS[$equipo['estado']] ?? $equipo['estado'];
        return $campos;
    }

    // ---------------------------------------------------------------------
    // Validaciones y reglas de negocio
    // ---------------------------------------------------------------------

    /**
     * Valida el formulario de crear/editar. Devuelve ['campo' => 'mensaje']; vacío = todo bien.
     *
     * @param array      $datos  campos del formulario (ver EquipoController::leerFormulario)
     * @param array|null $actual el equipo tal como está en la base al editar; null al crear
     */
    public static function validar(array $datos, ?array $actual): array
    {
        $errores = [];
        $idEquipo = $actual === null ? 0 : (int) $actual['idEquipo'];

        foreach ($datos as $valor) {
            if (is_string($valor) && !mb_check_encoding($valor, 'UTF-8')) {
                return ['nombre' => 'El texto tiene caracteres no válidos. Escríbelo de nuevo.'];
            }
        }

        // Código de inventario: solo al crear (después no se cambia, va en contratos y etiquetas).
        if ($actual === null) {
            $codigo = $datos['codigoInventario'];
            if ($codigo === '') {
                $errores['codigoInventario'] = 'Escribe el código de inventario.';
            } elseif (mb_strlen($codigo) > 30) {
                $errores['codigoInventario'] = 'El código no puede tener más de 30 caracteres.';
            } elseif (!preg_match('/^[A-Z0-9-]+$/', $codigo)) {
                $errores['codigoInventario'] = 'Usa solo letras, números y guiones (por ejemplo EQ-1005).';
            } elseif ($datos['cantidad'] < 1 || $datos['cantidad'] > self::MAXIMO_UNIDADES) {
                $errores['cantidad'] = 'De 1 a ' . self::MAXIMO_UNIDADES . ' unidades.';
            } elseif (($codigos = self::codigosConsecutivos($codigo, $datos['cantidad'])) === null) {
                $errores['codigoInventario'] = 'Para registrar varias unidades, el código debe terminar en número (por ejemplo EQ-1005).';
            } elseif ($repetidos = self::codigosExistentes($codigos)) {
                $errores['codigoInventario'] = count($codigos) === 1 ? self::ERROR_CODIGO_REPETIDO
                    : 'Ya existen estos códigos: ' . implode(', ', $repetidos) . '. Cambia el código inicial o la cantidad.';
            }
        }

        // Número de serie: opcional, pero si se escribe no se puede repetir.
        $serie = $datos['numeroSerie'];
        if (mb_strlen($serie) > 80) {
            $errores['numeroSerie'] = 'El número de serie no puede tener más de 80 caracteres.';
        } elseif ($serie !== '' && self::existeSerie($serie, $idEquipo)) {
            $errores['numeroSerie'] = self::ERROR_SERIE_REPETIDA;
        }

        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'Escribe el nombre del equipo.';
        } elseif (mb_strlen($datos['nombre']) > 120) {
            $errores['nombre'] = 'El nombre no puede tener más de 120 caracteres.';
        }
        if (mb_strlen($datos['marca']) > 80) {
            $errores['marca'] = 'La marca no puede tener más de 80 caracteres.';
        }
        if (mb_strlen($datos['modelo']) > 80) {
            $errores['modelo'] = 'El modelo no puede tener más de 80 caracteres.';
        }

        // Categoría: debe existir y estar activa. REGLA compartida con Categorías:
        // no se asignan equipos a una categoría inactiva. Al editar se permite
        // conservar la categoría que ya tenía, aunque se haya desactivado.
        $categoria = $datos['idCategoria'] > 0 ? Categoria::buscarPorId($datos['idCategoria']) : null;
        if ($categoria === null) {
            $errores['idCategoria'] = 'Elige la categoría del equipo.';
        } elseif (!$categoria['activa'] && ($actual === null || (int) $actual['idCategoria'] !== $datos['idCategoria'])) {
            $errores['idCategoria'] = "La categoría \"{$categoria['nombre']}\" está inactiva. Elige una categoría activa.";
        }

        // Fecha de adquisición: opcional, con formato AAAA-MM-DD (el que envía <input type="date">) y no futura.
        $fecha = $datos['fechaAdquisicion'];
        if ($fecha !== '') {
            $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);
            if ($fechaValida === false || $fechaValida->format('Y-m-d') !== $fecha) {
                $errores['fechaAdquisicion'] = 'La fecha de adquisición no es válida.';
            } elseif ($fecha > date('Y-m-d')) {
                $errores['fechaAdquisicion'] = 'La fecha de adquisición no puede ser futura.';
            }
        }

        $valor = $datos['valorReferencia'];
        if ($valor !== '' && (!is_numeric($valor) || (float) $valor < 0 || (float) $valor >= 10000000000)) {
            $errores['valorReferencia'] = 'El valor de referencia debe ser un número mayor o igual que cero.';
        }

        foreach (['descripcion' => 'La descripción', 'caracteristicasTecnicas' => 'Las características técnicas',
                  'condicionesUso' => 'Las condiciones de uso'] as $campo => $texto) {
            if (mb_strlen($datos[$campo]) > self::MAX_TEXTO_LARGO) {
                $errores[$campo] = "$texto no pueden pasar de " . self::MAX_TEXTO_LARGO . ' caracteres.';
            }
        }

        return $errores;
    }

    /**
     * ¿Se puede pasar el equipo a $nuevoEstado? Devuelve ['campo' => 'mensaje']; vacío = sí.
     */
    public static function validarCambioEstado(array $equipo, string $nuevoEstado, string $motivo): array
    {
        if (!$equipo['activo']) {
            return ['estado' => 'El equipo está dado de baja. Reactívalo antes de cambiar su estado.'];
        }
        // REGLA: "Alquilado" lo pone y lo quita el módulo de Operación (entrega y devolución).
        if ($equipo['estado'] === 'Alquilado') {
            return ['estado' => 'El equipo está alquilado: su estado cambia cuando Operación registre la devolución.'];
        }

        $errores = [];
        if (!in_array($nuevoEstado, self::ESTADOS_MANUALES, true)) {
            $errores['estado'] = 'Elige Disponible, En revisión, Mantenimiento o Fuera de servicio.';
        } elseif ($nuevoEstado === $equipo['estado']) {
            $errores['estado'] = 'El equipo ya está en ese estado.';
        }

        if ($motivo === '') {
            $errores['motivo'] = 'Escribe el motivo del cambio: queda en la bitácora del equipo.';
        } elseif (!mb_check_encoding($motivo, 'UTF-8')) {
            $errores['motivo'] = 'El texto tiene caracteres no válidos. Escríbelo de nuevo.';
        } elseif (mb_strlen($motivo) > 300) {
            $errores['motivo'] = 'El motivo no puede tener más de 300 caracteres.';
        }
        return $errores;
    }

    /**
     * ¿Se puede dar de baja (desactivar)? Devuelve ['campo' => 'mensaje']; vacío = sí.
     * La clave 'general' es un bloqueo que no depende de lo que se escribió en el modal.
     */
    public static function validarDesactivacion(array $equipo, string $motivo, string $detalle): array
    {
        if (!$equipo['activo']) {
            return ['general' => 'El equipo ya está dado de baja.'];
        }

        // REGLA: no se da de baja un equipo comprometido en un contrato sin terminar.
        $contrato = self::contratoAbierto((int) $equipo['idEquipo']);
        if ($contrato !== null) {
            return ['general' => "No se puede desactivar: el equipo está en el contrato {$contrato['numeroContrato']} "
                . "({$contrato['estado']}). Espera a que termine o quítalo de ese contrato primero."];
        }

        $errores = [];
        if (!in_array($motivo, self::MOTIVOS_BAJA, true)) {
            $errores['motivo'] = 'Elige el motivo de la baja.';
        }
        if ($motivo === 'Otro') {
            if ($detalle === '') {
                $errores['detalle'] = 'Describe el motivo de la baja.';
            } elseif (!mb_check_encoding($detalle, 'UTF-8')) {
                $errores['detalle'] = 'El texto tiene caracteres no válidos. Escríbelo de nuevo.';
            } elseif (mb_strlen($detalle) > 250) {
                $errores['detalle'] = 'El detalle no puede tener más de 250 caracteres.';
            }
        }
        return $errores;
    }

    /**
     * ¿Se puede reactivar? Devuelve null si sí, o el mensaje que explica por qué no.
     * REGLA compartida con Categorías: no hay equipos activos en categorías inactivas.
     */
    public static function validarReactivacion(array $equipo): ?string
    {
        if ($equipo['activo']) {
            return 'El equipo ya está activo.';
        }
        if (!$equipo['categoriaActiva']) {
            return "No se puede reactivar: la categoría \"{$equipo['categoria']}\" está inactiva. Actívala primero en Categorías.";
        }
        return null;
    }

    /**
     * Primer contrato Pendiente, Aprobado o Activo en el que el equipo tiene una línea
     * sin devolver, o null si no hay ninguno.
     */
    public static function contratoAbierto(int $idEquipo): ?array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT c.numeroContrato, c.estado
               FROM detalleContrato dc
               JOIN contrato c ON c.idContrato = dc.idContrato
              WHERE dc.idEquipo = ?
                AND c.estado IN ('Pendiente', 'Aprobado', 'Activo')
                AND NOT EXISTS (SELECT 1 FROM detalleDevolucion dd WHERE dd.idDetalleContrato = dc.idDetalleContrato)
              ORDER BY c.fechaInicio
              LIMIT 1"
        );
        $sentencia->execute([$idEquipo]);
        $fila = $sentencia->fetch();
        return $fila === false ? null : $fila;
    }

    // ---------------------------------------------------------------------
    // Consultas del listado
    // ---------------------------------------------------------------------

    /**
     * Arma el WHERE del listado a partir de los filtros. Las condiciones son textos
     * fijos escritos aquí; los valores del usuario van SIEMPRE en $parametros (con "?").
     *
     * @param array $filtros ['buscar', 'idCategoria', 'estado', 'activo' => '1' | '0' | 'todos']
     * @return array [string $where, array $parametros]
     */
    private static function condicionesListado(array $filtros): array
    {
        $condiciones = [];
        $parametros = [];

        if ($filtros['buscar'] !== '') {
            $condiciones[] = '(e.codigoInventario LIKE ? OR e.nombre LIKE ? OR e.numeroSerie LIKE ?)';
            $patron = '%' . $filtros['buscar'] . '%';
            array_push($parametros, $patron, $patron, $patron);
        }
        if ($filtros['idCategoria'] > 0) {
            $condiciones[] = 'e.idCategoria = ?';
            $parametros[] = $filtros['idCategoria'];
        }
        if ($filtros['estado'] !== '') {
            $condiciones[] = 'e.estado = ?';
            $parametros[] = $filtros['estado'];
        }
        if ($filtros['activo'] === '1') {
            $condiciones[] = 'e.activo = TRUE';
        } elseif ($filtros['activo'] === '0') {
            $condiciones[] = 'e.activo = FALSE';
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        return [$where, $parametros];
    }

    public static function contar(array $filtros): int
    {
        [$where, $parametros] = self::condicionesListado($filtros);
        $sentencia = Database::conexion()->prepare("SELECT COUNT(*) FROM equipo e $where");
        $sentencia->execute($parametros);
        return (int) $sentencia->fetchColumn();
    }

    /** Una página del listado, con la categoría y la tarifa diaria vigente. */
    public static function listar(array $filtros, int $limite, int $desplazamiento): array
    {
        [$where, $parametros] = self::condicionesListado($filtros);
        $sentencia = Database::conexion()->prepare(
            "SELECT e.idEquipo, e.codigoInventario, e.nombre, e.marca, e.modelo, e.numeroSerie,
                    e.descripcion, e.caracteristicasTecnicas, e.condicionesUso,
                    e.estado, e.activo, e.fechaAdquisicion, e.valorReferencia, e.idCategoria,
                    c.nombre AS categoria, tv.precioDiario
               FROM equipo e
               JOIN categoria c ON c.idCategoria = e.idCategoria
               LEFT JOIN v_tarifaDiariaVigente tv ON tv.idEquipo = e.idEquipo
               $where
              ORDER BY e.codigoInventario
              LIMIT ? OFFSET ?"
        );
        // Los ? de los filtros van primero (1, 2, ...), luego LIMIT y OFFSET como enteros.
        $posicion = 1;
        foreach ($parametros as $valor) {
            $sentencia->bindValue($posicion++, $valor);
        }
        $sentencia->bindValue($posicion++, $limite, PDO::PARAM_INT);
        $sentencia->bindValue($posicion, $desplazamiento, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    // ---------------------------------------------------------------------
    // Un equipo
    // ---------------------------------------------------------------------

    /** El equipo con su categoría (y si está activa) y su tarifa diaria vigente, o null. */
    public static function buscarPorId(int $idEquipo): ?array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT e.*, c.nombre AS categoria, c.activa AS categoriaActiva, tv.precioDiario, tv.depositoSugerido
               FROM equipo e
               JOIN categoria c ON c.idCategoria = e.idCategoria
               LEFT JOIN v_tarifaDiariaVigente tv ON tv.idEquipo = e.idEquipo
              WHERE e.idEquipo = ?'
        );
        $sentencia->execute([$idEquipo]);
        $fila = $sentencia->fetch();
        return $fila === false ? null : $fila;
    }


    /** ¿Otro equipo (distinto de $excepto) ya tiene ese número de serie? */
    public static function existeSerie(string $serie, int $excepto): bool
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT COUNT(*) FROM equipo WHERE numeroSerie = ? AND idEquipo <> ?'
        );
        $sentencia->execute([$serie, $excepto]);
        return (int) $sentencia->fetchColumn() > 0;
    }

    /**
     * Registra un equipo nuevo. Nace Disponible y el trigger lo anota en la bitácora
     * con @app_motivo. Devuelve el id.
     */
    /**
     * Registra $datos['cantidad'] unidades iguales con códigos consecutivos (EQ-1005,
     * EQ-1006...) en UNA transacción y un solo INSERT de varias filas. Con más de una
     * unidad, el número de serie queda vacío (cada unidad tiene el suyo). Nacen
     * Disponibles y el trigger las anota en la bitácora con @app_motivo.
     * Devuelve los códigos y el id de la primera.
     */
    public static function crear(array $datos): array
    {
        $codigos = self::codigosConsecutivos($datos['codigoInventario'], $datos['cantidad']);
        $serie = count($codigos) === 1 ? self::nuloSiVacio($datos['numeroSerie']) : null;
        $filas = [];
        $valores = [];
        foreach ($codigos as $codigo) {
            $filas[] = "(?, ?, ?, ?, ?, ?, ?, ?, 'Disponible', ?, ?, ?)";
            array_push($valores, $codigo, $datos['nombre'], self::nuloSiVacio($datos['marca']), self::nuloSiVacio($datos['modelo']),
                $serie, self::nuloSiVacio($datos['descripcion']), self::nuloSiVacio($datos['caracteristicasTecnicas']),
                self::nuloSiVacio($datos['condicionesUso']), self::nuloSiVacio($datos['fechaAdquisicion']),
                self::nuloSiVacio($datos['valorReferencia']), $datos['idCategoria']);
        }

        $conexion = Database::conexion();
        $conexion->beginTransaction();
        $conexion->prepare('SET @app_motivo = ?')->execute(['Registro del equipo en el inventario']);
        try {
            $conexion->prepare(
                'INSERT INTO equipo (codigoInventario, nombre, marca, modelo, numeroSerie, descripcion,
                                     caracteristicasTecnicas, condicionesUso, estado, fechaAdquisicion,
                                     valorReferencia, idCategoria)
                 VALUES ' . implode(', ', $filas)
            )->execute($valores);
            $idPrimero = (int) $conexion->lastInsertId(); // con varias filas, MySQL da el id de la primera
            $conexion->commit();
            return ['codigos' => $codigos, 'idEquipo' => $idPrimero];
        } catch (Throwable $e) {
            $conexion->rollBack(); // si una unidad falla, no queda ninguna
            throw $e;
        } finally {
            // finally se ejecuta aunque haya error: el motivo no se queda para el siguiente cambio.
            $conexion->exec('SET @app_motivo = NULL');
        }
    }

    /**
     * Códigos de $cantidad unidades a partir del primero, con el mismo número de cifras:
     * ('EQ-1005', 3) -> EQ-1005, EQ-1006, EQ-1007; ('EQ-009', 2) -> EQ-009, EQ-010.
     * Devuelve null si se piden varias y el código no termina en número.
     */
    public static function codigosConsecutivos(string $codigo, int $cantidad): ?array
    {
        if ($cantidad === 1) {
            return [$codigo];
        }
        if (!preg_match('/^(.*?)(\d+)$/', $codigo, $partes)) {
            return null;
        }
        [, $prefijo, $numero] = $partes;
        $codigos = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $codigos[] = $prefijo . str_pad((string) ((int) $numero + $i), strlen($numero), '0', STR_PAD_LEFT);
        }
        return $codigos;
    }

    /** De los códigos dados, los que ya existen, en una sola consulta (utf8mb4_spanish_ci: no distingue mayúsculas). */
    private static function codigosExistentes(array $codigos): array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT codigoInventario FROM equipo WHERE codigoInventario IN (' . implode(', ', array_fill(0, count($codigos), '?')) . ')'
        );
        $sentencia->execute($codigos);
        return $sentencia->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Actualiza la ficha. No toca el código, el estado ni si está activo (tienen su propia acción). */
    public static function actualizar(int $idEquipo, array $datos): void
    {
        $sentencia = Database::conexion()->prepare(
            'UPDATE equipo
                SET nombre = ?, marca = ?, modelo = ?, numeroSerie = ?, descripcion = ?,
                    caracteristicasTecnicas = ?, condicionesUso = ?, fechaAdquisicion = ?,
                    valorReferencia = ?, idCategoria = ?
              WHERE idEquipo = ?'
        );
        $sentencia->execute([
            $datos['nombre'],
            self::nuloSiVacio($datos['marca']),
            self::nuloSiVacio($datos['modelo']),
            self::nuloSiVacio($datos['numeroSerie']),
            self::nuloSiVacio($datos['descripcion']),
            self::nuloSiVacio($datos['caracteristicasTecnicas']),
            self::nuloSiVacio($datos['condicionesUso']),
            self::nuloSiVacio($datos['fechaAdquisicion']),
            self::nuloSiVacio($datos['valorReferencia']),
            $datos['idCategoria'],
            $idEquipo,
        ]);
    }

    /** Cambia el estado; el trigger lo anota en la bitácora con el motivo. */
    public static function cambiarEstado(int $idEquipo, string $nuevoEstado, string $motivo): void
    {
        $conexion = Database::conexion();
        $conexion->prepare('SET @app_motivo = ?')->execute([$motivo]);
        try {
            $conexion->prepare('UPDATE equipo SET estado = ? WHERE idEquipo = ?')->execute([$nuevoEstado, $idEquipo]);
        } finally {
            $conexion->exec('SET @app_motivo = NULL');
        }
    }

    /**
     * Baja del equipo, en UNA transacción: pasa a Fuera de servicio y activo = FALSE.
     * El motivo queda en la bitácora (fecha y usuario los pone el trigger).
     */
    public static function desactivar(array $equipo, string $motivo, string $detalle): void
    {
        // Texto de la bitácora: "Baja del equipo: Venta" o "Baja del equipo: Otro - <detalle>".
        $texto = 'Baja del equipo: ' . ($motivo === 'Otro' ? 'Otro - ' . $detalle : $motivo);

        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            self::cambiarEstadoYActivo($equipo, 'FueraServicio', false, $texto);
            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollBack(); // si algo falla, no queda nada a medias
            throw $e;
        }
    }

    /** Reactivación, en UNA transacción: vuelve a Disponible y activo = TRUE. */
    public static function reactivar(array $equipo): void
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            self::cambiarEstadoYActivo($equipo, 'Disponible', true, 'Reactivación del equipo');
            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollBack();
            throw $e;
        }
    }

    /**
     * Cambia estado y activo con @app_motivo. Si el estado no cambia (por ejemplo, ya
     * estaba Fuera de servicio), el trigger no anota nada; entonces se anota a mano,
     * para que la baja o la reactivación siempre quede en la bitácora.
     */
    private static function cambiarEstadoYActivo(array $equipo, string $estado, bool $activo, string $motivo): void
    {
        $conexion = Database::conexion();
        $conexion->prepare('SET @app_motivo = ?')->execute([$motivo]);
        try {
            $conexion->prepare('UPDATE equipo SET estado = ?, activo = ? WHERE idEquipo = ?')
                ->execute([$estado, $activo ? 1 : 0, $equipo['idEquipo']]);

            if ($equipo['estado'] === $estado) {
                $conexion->prepare(
                    'INSERT INTO bitacoraEstadoEquipo (estadoAnterior, estadoNuevo, motivo, idEquipo, idUsuario)
                     VALUES (?, ?, ?, ?, @app_idUsuario)'
                )->execute([$estado, $estado, $motivo, $equipo['idEquipo']]);
            }
        } finally {
            $conexion->exec('SET @app_motivo = NULL');
        }
    }

    /**
     * Equipos dados de baja con su última baja de la bitácora (fecha, motivo y quién),
     * en UNA consulta: ROW_NUMBER() se queda con la baja más reciente de cada equipo.
     */
    public static function inactivos(): array
    {
        return Database::conexion()->query(
            "SELECT e.idEquipo, e.codigoInventario, e.nombre, c.nombre AS categoria, c.activa AS categoriaActiva,
                    b.fechaCambio AS fechaBaja, REPLACE(b.motivo, 'Baja del equipo: ', '') AS motivo,
                    CONCAT(u.nombres, ' ', u.apellidos) AS usuario
               FROM equipo e
               JOIN categoria c ON c.idCategoria = e.idCategoria
               LEFT JOIN (SELECT idEquipo, fechaCambio, motivo, idUsuario,
                                 ROW_NUMBER() OVER (PARTITION BY idEquipo ORDER BY fechaCambio DESC, idBitacora DESC) AS numero
                            FROM bitacoraEstadoEquipo
                           WHERE motivo LIKE 'Baja del equipo%') b ON b.idEquipo = e.idEquipo AND b.numero = 1
               LEFT JOIN usuario u ON u.idUsuario = b.idUsuario
              WHERE e.activo = FALSE
              ORDER BY b.fechaCambio DESC, e.codigoInventario"
        )->fetchAll();
    }

    /** Equipos activos (id, código y nombre) para la lista del modal de baja. */
    public static function activosParaLista(): array
    {
        return Database::conexion()->query(
            'SELECT idEquipo, codigoInventario, nombre FROM equipo WHERE activo = TRUE ORDER BY codigoInventario'
        )->fetchAll();
    }

    /** Historial de estados (bitácora), del más reciente al más antiguo, con el nombre del usuario. */
    public static function historialEstados(int $idEquipo): array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT b.fechaCambio, b.estadoAnterior, b.estadoNuevo, b.motivo,
                    CONCAT(u.nombres, ' ', u.apellidos) AS usuario
               FROM bitacoraEstadoEquipo b
               JOIN usuario u ON u.idUsuario = b.idUsuario
              WHERE b.idEquipo = ?
              ORDER BY b.fechaCambio DESC, b.idBitacora DESC"
        );
        $sentencia->execute([$idEquipo]);
        return $sentencia->fetchAll();
    }

    /** Texto vacío -> NULL en la base (para columnas opcionales). */
    private static function nuloSiVacio(string $valor): ?string
    {
        return $valor === '' ? null : $valor;
    }
}
