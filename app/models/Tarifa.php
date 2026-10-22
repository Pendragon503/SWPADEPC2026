<?php
/**
 * EquipRent - Modelo de la tabla tarifa.
 *
 * Cada equipo tiene una tarifa abierta (sin fecha final) por tipo de periodo.
 * Al crear una nueva del mismo tipo, la abierta se cierra el día anterior al inicio
 * de la nueva, en la misma transacción. Las tarifas no se editan ni se borran:
 * para corregir una se crea otra y la anterior queda en el historial.
 *
 * El catálogo usa la vista v_tarifaDiariaVigente (la tarifa por DÍA vigente hoy).
 */
declare(strict_types=1);

class Tarifa
{
    /** Tipos de periodo de la base => texto para el usuario. */
    public const PERIODOS = [
        'Dia'    => 'Por día',
        'Semana' => 'Por semana',
        'Mes'    => 'Por mes',
    ];

    // ---------------------------------------------------------------------
    // Validaciones
    // ---------------------------------------------------------------------

    /**
     * Valida una tarifa nueva. Devuelve ['campo' => 'mensaje']; vacío = todo bien.
     *
     * @param array $datos ['tipoPeriodo', 'monto', 'depositoSugerido', 'fechaInicioVigencia']
     */
    public static function validar(array $equipo, array $datos): array
    {
        if (!$equipo['activo']) {
            return ['tipoPeriodo' => 'El equipo está dado de baja: no se le pueden agregar tarifas.'];
        }

        $errores = [];
        if (!isset(self::PERIODOS[$datos['tipoPeriodo']])) {
            $errores['tipoPeriodo'] = 'Elige si la tarifa es por día, por semana o por mes.';
        }

        if (!is_numeric($datos['monto']) || (float) $datos['monto'] <= 0 || (float) $datos['monto'] >= 100000000) {
            $errores['monto'] = 'El monto debe ser un número mayor que cero.';
        }

        $deposito = $datos['depositoSugerido'];
        if ($deposito !== '' && (!is_numeric($deposito) || (float) $deposito < 0 || (float) $deposito >= 100000000)) {
            $errores['depositoSugerido'] = 'El depósito debe ser un número mayor o igual que cero.';
        }

        $inicio = $datos['fechaInicioVigencia'];
        $fecha = DateTime::createFromFormat('Y-m-d', $inicio);
        if ($inicio === '' || $fecha === false || $fecha->format('Y-m-d') !== $inicio) {
            $errores['fechaInicioVigencia'] = 'Elige la fecha desde la que rige la tarifa.';
        } elseif (!isset($errores['tipoPeriodo'])) {
            // REGLA: la nueva debe empezar después que la abierta del mismo tipo, que se cerrará un día antes.
            $abierta = self::abiertaDelTipo((int) $equipo['idEquipo'], $datos['tipoPeriodo']);
            if ($abierta !== null && $inicio <= $abierta['fechaInicioVigencia']) {
                $errores['fechaInicioVigencia'] = 'Debe ser posterior al ' . fechaCorta($abierta['fechaInicioVigencia'])
                    . ', inicio de la tarifa ' . mb_strtolower(self::PERIODOS[$datos['tipoPeriodo']]) . ' actual.';
            }
        }
        return $errores;
    }

    // ---------------------------------------------------------------------
    // Consultas y cambios
    // ---------------------------------------------------------------------

    /**
     * Tarifas del equipo con su situación:
     *   Vigente    activa y rige hoy
     *   Programada activa y empieza después de hoy
     *   Historica  ya terminó o se desactivó
     */
    public static function listarPorEquipo(int $idEquipo): array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT idTarifa, tipoPeriodo, monto, depositoSugerido, fechaInicioVigencia, fechaFinVigencia, activa,
                    CASE
                      WHEN activa = TRUE AND fechaInicioVigencia > CURDATE() THEN 'Programada'
                      WHEN activa = TRUE AND (fechaFinVigencia IS NULL OR fechaFinVigencia >= CURDATE()) THEN 'Vigente'
                      ELSE 'Historica'
                    END AS situacion
               FROM tarifa
              WHERE idEquipo = ?
              ORDER BY FIELD(tipoPeriodo, 'Dia', 'Semana', 'Mes'), fechaInicioVigencia DESC, idTarifa DESC"
        );
        $sentencia->execute([$idEquipo]);
        return $sentencia->fetchAll();
    }

    /** La tarifa activa y sin fecha final de ese tipo (la que se cierra al crear otra), o null. */
    public static function abiertaDelTipo(int $idEquipo, string $tipoPeriodo): ?array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT idTarifa, fechaInicioVigencia
               FROM tarifa
              WHERE idEquipo = ? AND tipoPeriodo = ? AND activa = TRUE AND fechaFinVigencia IS NULL
              ORDER BY fechaInicioVigencia DESC, idTarifa DESC
              LIMIT 1'
        );
        $sentencia->execute([$idEquipo, $tipoPeriodo]);
        $fila = $sentencia->fetch();
        return $fila === false ? null : $fila;
    }

    /**
     * Crea la tarifa y, en la MISMA transacción, cierra la abierta del mismo tipo el
     * día anterior al inicio de la nueva. Si algo falla, no se cierra ni se crea nada.
     */
    public static function crear(int $idEquipo, array $datos): void
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $abierta = self::abiertaDelTipo($idEquipo, $datos['tipoPeriodo']);
            if ($abierta !== null) {
                $conexion->prepare(
                    'UPDATE tarifa SET fechaFinVigencia = DATE_SUB(?, INTERVAL 1 DAY) WHERE idTarifa = ?'
                )->execute([$datos['fechaInicioVigencia'], $abierta['idTarifa']]);
            }

            $conexion->prepare(
                'INSERT INTO tarifa (tipoPeriodo, monto, depositoSugerido, fechaInicioVigencia, activa, idEquipo)
                 VALUES (?, ?, ?, ?, TRUE, ?)'
            )->execute([
                $datos['tipoPeriodo'],
                $datos['monto'],
                $datos['depositoSugerido'] === '' ? 0 : $datos['depositoSugerido'],
                $datos['fechaInicioVigencia'],
                $idEquipo,
            ]);
            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollBack();
            throw $e;
        }
    }
}
