<?php
/**
 * EquipRent - Devoluciones (RF-10), estados tras la devolución (RF-11) y daños:
 * tablas devolucion + detalleDevolucion + dano.
 *
 * Penalización por atraso, por equipo: días de atraso (contra contrato.fechaFin) ×
 * su tarifa diaria aplicada en el contrato. Usa las validaciones de Entrega.
 */
declare(strict_types=1);

class Devolucion
{
    public const SEVERIDADES = ['Leve', 'Moderado', 'Grave'];

    /** Filas de daños que ofrece el formulario por equipo (las vacías se ignoran). */
    public const FILAS_DANOS = 3;

    // ---------------------------------------------------------------------
    // Validaciones
    // ---------------------------------------------------------------------

    /**
     * ¿Se puede recibir? Solo equipos entregados y sin devolver, en un contrato Activo,
     * con una fecha no futura ni anterior a la entrega de cada equipo.
     * Devuelve ['campo' => 'mensaje']; vacío = válido.
     */
    public static function validar(array $contrato, array $lineas, array $datos): array
    {
        if ($contrato['estado'] !== 'Activo') {
            return ['general' => "Solo se reciben equipos de contratos Activos. {$contrato['numeroContrato']} está {$contrato['estado']}."];
        }
        $errores = Entrega::validarFecha($datos['fecha']);
        if (!$datos['equipos']) {
            $errores['equipos'] = 'Marca al menos un equipo para recibir.';
        }
        $porId = array_column($lineas, null, 'idDetalleContrato');
        foreach ($datos['equipos'] as $id => $equipo) {
            $linea = $porId[$id] ?? null;
            if ($linea === null || $linea['fechaEntrega'] === null || $linea['fechaDevolucion'] !== null) {
                $errores['equipos'] = 'Uno de los equipos marcados no se ha entregado o ya se recibió. Recarga la página.';
                continue;
            }
            // Fechas como texto "AAAA-MM-DD HH:MM": se comparan bien en orden alfabético.
            if (!isset($errores['fecha']) && str_replace('T', ' ', $datos['fecha']) < substr($linea['fechaEntrega'], 0, 16)) {
                $errores['fecha'] = "No puede ser anterior a la entrega de {$linea['nombre']} (" . fechaCorta($linea['fechaEntrega'], true) . ').';
            }
            $horometroSalida = $linea['horometroSalida'] === null ? null : (float) $linea['horometroSalida'];
            $errores += Entrega::validarEquipo($id, $equipo, $horometroSalida) + self::validarDanos($id, $equipo);
        }
        return $errores;
    }

    /** Si el equipo "tiene daños", al menos uno, cada uno con severidad y costo válidos. */
    private static function validarDanos(int $id, array $equipo): array
    {
        if (($equipo['tieneDanos'] ?? '') !== 'Si') {
            return [];
        }
        $danos = self::danos($equipo);
        if (!$danos) {
            return ["equipos[$id][danos]" => 'Describe al menos un daño, o elige "No" si no tiene.'];
        }
        $errores = [];
        foreach ($danos as $n => $dano) {
            $campo = "equipos[$id][danos][$n]";
            if (mb_strlen($dano['descripcion']) > 500) {
                $errores[$campo . '[descripcion]'] = 'Máximo 500 caracteres.';
            }
            if (!in_array($dano['severidad'], self::SEVERIDADES, true)) {
                $errores[$campo . '[severidad]'] = 'Elige la severidad.';
            }
            if ($dano['costo'] === false || $dano['costo'] < 0) {
                $errores[$campo . '[costo]'] = 'Costo no válido.';
            }
        }
        return $errores;
    }

    /** Daños escritos para un equipo (solo las filas con descripción), ya limpios. */
    public static function danos(array $equipo): array
    {
        if (($equipo['tieneDanos'] ?? '') !== 'Si') {
            return [];
        }
        $danos = [];
        foreach ((array) ($equipo['danos'] ?? []) as $n => $fila) {
            if (is_array($fila) && Entrega::texto($fila['descripcion'] ?? '') !== '') {
                $danos[(int) $n] = [
                    'descripcion' => Entrega::texto($fila['descripcion']),
                    'severidad'   => Entrega::texto($fila['severidad'] ?? ''),
                    'costo'       => Entrega::numero($fila['costo'] ?? '') ?? 0.0, // vacío = 0
                    'cobrar'      => !empty($fila['cobrar']),
                ];
            }
        }
        return $danos;
    }

    // ---------------------------------------------------------------------
    // Registrar
    // ---------------------------------------------------------------------

    /**
     * Registra la devolución (ya validada) en UNA transacción:
     *   1. devolución con días de atraso y penalización total; una línea por equipo
     *   2. sus daños (un solo INSERT de varias filas)
     *   3. equipos sin daños -> Disponible; con daños -> Revision (bitácora con el motivo)
     *   4. si ya regresaron todos los equipos, el contrato pasa a Finalizado
     * Devuelve null si se registró, o el mensaje de por qué no.
     */
    public static function registrar(array $contrato, array $lineas, array $datos, int $idUsuario): ?string
    {
        $porId = array_column($lineas, null, 'idDetalleContrato');
        $atraso = diasAtraso($contrato['fechaFin'], $datos['fecha']);
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            Contrato::bloquear((int) $contrato['idContrato']);

            $filas = [];
            $valores = [];
            $total = 0.0;
            foreach ($datos['equipos'] as $id => $equipo) {
                $penalizacion = round($atraso * (float) $porId[$id]['precioDiario'], 2);
                $total += $penalizacion;
                $filas[] = '(?, ?, ?, ?, ?, ?, @idDevolucion, ?)';
                array_push($valores, Entrega::texto($equipo['estadoFisico']), Entrega::numero($equipo['nivelCombustible'] ?? ''),
                    Entrega::numero($equipo['lecturaHorometro'] ?? ''), Entrega::nulo($equipo['accesorios'] ?? ''),
                    sprintf('%.2f', $penalizacion), Entrega::nulo($equipo['observaciones'] ?? ''), $id);
            }

            $conexion->prepare(
                'INSERT INTO devolucion (fechaDevolucion, diasAtraso, penalizacionTotal, idContrato, idRegistradoPor) VALUES (?, ?, ?, ?, ?)'
            )->execute([str_replace('T', ' ', $datos['fecha']), $atraso, sprintf('%.2f', $total), $contrato['idContrato'], $idUsuario]);
            $conexion->exec('SET @idDevolucion = LAST_INSERT_ID()'); // lo usan las filas "(…, @idDevolucion, ?)"
            $conexion->prepare(
                'INSERT INTO detalleDevolucion (estadoFisico, nivelCombustible, lecturaHorometro, accesoriosRecibidos,
                                                penalizacion, observaciones, idDevolucion, idDetalleContrato)
                 VALUES ' . implode(', ', $filas)
            )->execute($valores);

            // Daños: hacen falta los id de las líneas recién creadas (una consulta).
            $idsDevolucion = $conexion->query('SELECT idDetalleContrato, idDetalleDevolucion FROM detalleDevolucion WHERE idDevolucion = @idDevolucion')
                ->fetchAll(PDO::FETCH_KEY_PAIR);
            $filas = [];
            $valores = [];
            foreach ($datos['equipos'] as $id => $equipo) {
                foreach (self::danos($equipo) as $dano) {
                    $filas[] = '(?, ?, ?, ?, ?)';
                    array_push($valores, $dano['descripcion'], $dano['severidad'], sprintf('%.2f', $dano['costo']), (int) $dano['cobrar'], $idsDevolucion[$id]);
                }
            }
            if ($filas) {
                $conexion->prepare('INSERT INTO dano (descripcion, severidad, costoEstimado, cargoAlCliente, idDetalleDevolucion) VALUES ' . implode(', ', $filas))
                    ->execute($valores);
            }

            // RF-11: estado de cada equipo según tenga daños o no.
            $conDanos = array_keys(array_filter($datos['equipos'], [self::class, 'danos']));
            $sinDanos = array_diff(array_keys($datos['equipos']), $conDanos);
            Entrega::cambiarEstadoEquipos($sinDanos, 'Disponible', "Devolución del contrato {$contrato['numeroContrato']}");
            Entrega::cambiarEstadoEquipos($conDanos, 'Revision', "Devolución con daños del contrato {$contrato['numeroContrato']}");

            // Finalizado cuando ya no queda ninguna línea sin devolver.
            $conexion->prepare(
                "UPDATE contrato SET estado = 'Finalizado'
                  WHERE idContrato = ? AND estado = 'Activo'
                    AND NOT EXISTS (SELECT 1 FROM detalleContrato dc
                                     WHERE dc.idContrato = ?
                                       AND NOT EXISTS (SELECT 1 FROM detalleDevolucion dd WHERE dd.idDetalleContrato = dc.idDetalleContrato))"
            )->execute([$contrato['idContrato'], $contrato['idContrato']]);

            $conexion->commit();
            return null;
        } catch (PDOException $e) {
            $conexion->rollBack();
            if (esValorDuplicado($e, 'uq_detalleDevolucion_linea')) {
                return 'Uno de los equipos ya se recibió: otro usuario lo registró primero. Recarga la página.';
            }
            throw $e;
        }
    }
}
