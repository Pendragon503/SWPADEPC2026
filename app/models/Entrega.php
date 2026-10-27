<?php
/**
 * EquipRent - Entregas de equipos (RF-09): tablas entrega + detalleEntrega.
 *
 * Se permiten entregas parciales: cada envío entrega los equipos marcados. Un equipo
 * no se entrega dos veces (lo revisa validar() y, si dos personas lo envían a la
 * vez, el UNIQUE(idDetalleContrato) de la base). Las validaciones de fecha y de los
 * datos de cada equipo las comparte Devolucion.
 */
declare(strict_types=1);

class Entrega
{
    /** Estado físico de un equipo al salir o al regresar. */
    public const ESTADOS_FISICOS = ['Buen estado', 'Desgaste normal', 'Detalles menores', 'Dañado'];

    // ---------------------------------------------------------------------
    // Validaciones (también las usa Devolucion)
    // ---------------------------------------------------------------------

    /**
     * ¿Se puede entregar? Devuelve ['campo' => 'mensaje']; vacío = válido.
     *
     * @param array $lineas Contrato::lineas()
     * @param array $datos  ['fecha', 'nombreReceptor', 'documentoReceptor', 'observaciones',
     *                       'equipos' => [idDetalleContrato => campos del equipo]] (solo los marcados)
     */
    public static function validar(array $contrato, array $lineas, array $datos): array
    {
        if (!in_array($contrato['estado'], ['Aprobado', 'Activo'], true)) {
            return ['general' => "Solo se entregan equipos de contratos Aprobados o Activos. {$contrato['numeroContrato']} está {$contrato['estado']}."];
        }
        $errores = self::validarFecha($datos['fecha']) + self::validarLargos($datos, [
            'nombreReceptor' => [160, 'el nombre de quien recibe'],
            'documentoReceptor' => [25, 'el documento de quien recibe'],
        ], true) + self::validarLargos($datos, ['observaciones' => [500, 'las observaciones']], false);
        // No se entrega antes del inicio: el equipo saldría en días que la base considera libres.
        if (!isset($errores['fecha']) && substr($datos['fecha'], 0, 10) < $contrato['fechaInicio']) {
            $errores['fecha'] = 'No se puede entregar antes del inicio del contrato (' . fechaCorta($contrato['fechaInicio'])
                . '). Si el cliente lo necesita antes, cambia primero las fechas del contrato.';
        }

        // Solo equipos de este contrato, sin entregar y listos para salir.
        $porId = array_column($lineas, null, 'idDetalleContrato');
        if (!$datos['equipos']) {
            $errores['equipos'] = 'Marca al menos un equipo para entregar.';
        }
        foreach ($datos['equipos'] as $id => $equipo) {
            $linea = $porId[$id] ?? null;
            if ($linea === null || $linea['fechaEntrega'] !== null) {
                $errores['equipos'] = 'Uno de los equipos marcados ya se entregó o no es de este contrato. Recarga la página.';
            } elseif (!$linea['listoParaSalir']) {
                $errores['equipos'] = "{$linea['nombre']} no puede salir: debe estar Disponible y sin mantenimiento abierto.";
            }
            $errores += self::validarEquipo($id, $equipo);
        }
        return $errores;
    }

    /** Fecha y hora (AAAA-MM-DDTHH:MM, del input datetime-local) que no sea futura. */
    public static function validarFecha(string $fecha): array
    {
        $valor = DateTime::createFromFormat('Y-m-d\TH:i', $fecha);
        if ($valor === false || $valor->format('Y-m-d\TH:i') !== $fecha) {
            return ['fecha' => 'Escribe la fecha y la hora.'];
        }
        return $valor > new DateTime() ? ['fecha' => 'La fecha y la hora no pueden ser futuras.'] : [];
    }

    /**
     * Datos de un equipo: estado físico de la lista, combustible de 0 a 100 %, horómetro
     * mayor o igual a $horometroMinimo (el de salida, al recibir), accesorios y observaciones.
     * Las claves de error llevan el nombre del campo: "equipos[5][lecturaHorometro]".
     */
    public static function validarEquipo(int $id, array $equipo, ?float $horometroMinimo = null): array
    {
        $errores = [];
        $campo = function (string $nombre) use ($id): string {
            return "equipos[$id][$nombre]";
        };
        if (!in_array(self::texto($equipo['estadoFisico'] ?? ''), self::ESTADOS_FISICOS, true)) {
            $errores[$campo('estadoFisico')] = 'Elige el estado físico.';
        }
        $combustible = self::numero($equipo['nivelCombustible'] ?? '');
        if ($combustible === false || ($combustible !== null && ($combustible < 0 || $combustible > 100))) {
            $errores[$campo('nivelCombustible')] = 'De 0 a 100 %.';
        }
        $horometro = self::numero($equipo['lecturaHorometro'] ?? '');
        if ($horometro === false || ($horometro !== null && $horometro < 0)) {
            $errores[$campo('lecturaHorometro')] = 'Escribe un número de horas válido.';
        } elseif ($horometro !== null && $horometroMinimo !== null && $horometro < $horometroMinimo) {
            $errores[$campo('lecturaHorometro')] = 'No puede ser menor que el de salida (' . $horometroMinimo . ' h).';
        }
        if (mb_strlen(self::texto($equipo['accesorios'] ?? '')) > 300) {
            $errores[$campo('accesorios')] = 'Máximo 300 caracteres.';
        }
        if (mb_strlen(self::texto($equipo['observaciones'] ?? '')) > 500) {
            $errores[$campo('observaciones')] = 'Máximo 500 caracteres.';
        }
        return $errores;
    }

    /** Campos de texto obligatorios u opcionales con su largo máximo: [campo => [largo, 'el texto']]. */
    private static function validarLargos(array $datos, array $campos, bool $obligatorios): array
    {
        $errores = [];
        foreach ($campos as $nombre => [$largo, $texto]) {
            $valor = $datos[$nombre];
            if (!mb_check_encoding($valor, 'UTF-8')) {
                $errores[$nombre] = 'El texto tiene caracteres no válidos. Escríbelo de nuevo.';
            } elseif ($obligatorios && $valor === '') {
                $errores[$nombre] = "Escribe $texto.";
            } elseif (mb_strlen($valor) > $largo) {
                $errores[$nombre] = "Máximo $largo caracteres.";
            }
        }
        return $errores;
    }

    // ---------------------------------------------------------------------
    // Ayudantes (también los usa Devolucion)
    // ---------------------------------------------------------------------

    /** Estados que ofrece el modal de entrega (al salir, un equipo no va "Dañado"). */
    public const ESTADOS_SALIDA = ['Buen estado', 'Desgaste normal', 'Detalles menores'];

    /** Líneas del contrato que se pueden entregar ahora: sin entregar y listas para salir. */
    public static function pendientes(array $lineas): array
    {
        return array_values(array_filter($lineas, function (array $linea): bool {
            return $linea['fechaEntrega'] === null && $linea['listoParaSalir'];
        }));
    }

    /**
     * Modal de entrega -> equipos[idDetalleContrato] => campos, el formato que esperan
     * validar() y registrar(). $eleccion es un idDetalleContrato o 'todos' (todos los
     * pendientes, con el mismo estado y los mismos detalles de salida).
     */
    public static function equiposDelModal(array $lineas, string $eleccion, array $campos): array
    {
        $ids = $eleccion === 'todos' ? array_column(self::pendientes($lineas), 'idDetalleContrato') : [(int) $eleccion];
        return array_fill_keys(array_filter($ids), $campos + ['marcado' => '1']);
    }

    /**
     * Errores de validar() con los nombres de los campos del modal: "equipos[5][estadoFisico]"
     * -> "estadoFisico" (todos los equipos llevan los mismos datos) y "equipos" -> "equipo".
     */
    public static function erroresDelModal(array $errores): array
    {
        $modal = [];
        foreach ($errores as $clave => $mensaje) {
            $campo = preg_replace('/^equipos\[\d+\]\[(\w+)\]$/', '$1', $clave);
            $modal[$campo === 'equipos' ? 'equipo' : $campo] = $mensaje;
        }
        return $modal;
    }

    /** De equipos[id][...] del formulario, solo los que tienen la casilla "marcado". */
    public static function equiposMarcados(array $equipos): array
    {
        return array_filter($equipos, function ($campos): bool {
            return is_array($campos) && !empty($campos['marcado']);
        });
    }

    public static function texto($valor): string
    {
        return is_string($valor) ? trim($valor) : '';
    }

    /** Número opcional: null si viene vacío, false si no es un número. */
    public static function numero($valor)
    {
        $valor = self::texto($valor);
        if ($valor === '') {
            return null;
        }
        return is_numeric($valor) ? (float) $valor : false;
    }

    /** Texto opcional para la base: null si viene vacío. */
    public static function nulo($valor): ?string
    {
        $valor = self::texto($valor);
        return $valor === '' ? null : $valor;
    }

    /**
     * Cambia el estado de varios equipos en UN UPDATE, con @app_motivo para la bitácora
     * (@app_idUsuario lo pone Database). $estadoActual, si se da, debe coincidir en
     * todos: si alguno cambió mientras tanto, devuelve false.
     */
    public static function cambiarEstadoEquipos(array $idsDetalle, string $estado, string $motivo, ?string $estadoActual = null): bool
    {
        if (!$idsDetalle) {
            return true;
        }
        $conexion = Database::conexion();
        $marcas = implode(', ', array_fill(0, count($idsDetalle), '?'));
        $conexion->prepare('SET @app_motivo = ?')->execute([$motivo]);
        try {
            $sentencia = $conexion->prepare(
                "UPDATE equipo SET estado = ?
                  WHERE idEquipo IN (SELECT idEquipo FROM detalleContrato WHERE idDetalleContrato IN ($marcas))"
                . ($estadoActual === null ? '' : ' AND estado = ?')
            );
            $sentencia->execute(array_merge([$estado], array_values($idsDetalle), $estadoActual === null ? [] : [$estadoActual]));
            return $estadoActual === null || $sentencia->rowCount() === count($idsDetalle);
        } finally {
            $conexion->exec('SET @app_motivo = NULL'); // el motivo no se queda para el siguiente cambio
        }
    }

    // ---------------------------------------------------------------------
    // Registrar
    // ---------------------------------------------------------------------

    /**
     * Registra la entrega (ya validada) en UNA transacción:
     *   1. entrega y sus líneas (un solo INSERT de varias filas)
     *   2. cada equipo pasa de Disponible a Alquilado (bitácora con el motivo)
     *   3. el contrato pasa a Activo si estaba Aprobado
     * Devuelve null si se registró, o el mensaje de por qué no.
     */
    public static function registrar(array $contrato, array $datos, int $idUsuario): ?string
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            Contrato::bloquear((int) $contrato['idContrato']);
            $conexion->prepare(
                'INSERT INTO entrega (fechaEntrega, nombreReceptor, documentoReceptor, observaciones, idContrato, idRegistradoPor)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([str_replace('T', ' ', $datos['fecha']), $datos['nombreReceptor'], $datos['documentoReceptor'],
                self::nulo($datos['observaciones']), $contrato['idContrato'], $idUsuario]);
            $idEntrega = (int) $conexion->lastInsertId();

            $filas = [];
            $valores = [];
            foreach ($datos['equipos'] as $id => $equipo) {
                $filas[] = '(?, ?, ?, ?, ?, ?)';
                array_push($valores, self::texto($equipo['estadoFisico']), self::numero($equipo['nivelCombustible'] ?? ''),
                    self::numero($equipo['lecturaHorometro'] ?? ''), self::nulo($equipo['accesorios'] ?? ''), $idEntrega, $id);
            }
            $conexion->prepare(
                'INSERT INTO detalleEntrega (estadoFisico, nivelCombustible, lecturaHorometro, accesoriosEntregados, idEntrega, idDetalleContrato)
                 VALUES ' . implode(', ', $filas)
            )->execute($valores);

            if (!self::cambiarEstadoEquipos(array_keys($datos['equipos']), 'Alquilado', "Entrega del contrato {$contrato['numeroContrato']}", 'Disponible')) {
                $conexion->rollBack();
                return 'Uno de los equipos cambió de estado mientras registrabas la entrega. Recarga la página.';
            }
            $conexion->prepare("UPDATE contrato SET estado = 'Activo' WHERE idContrato = ? AND estado = 'Aprobado'")
                ->execute([$contrato['idContrato']]);

            $conexion->commit();
            return null;
        } catch (PDOException $e) {
            $conexion->rollBack();
            if (esValorDuplicado($e, 'uq_detalleEntrega_linea')) {
                return 'Uno de los equipos ya se entregó: otro usuario lo registró primero. Recarga la página.';
            }
            throw $e;
        }
    }
}
