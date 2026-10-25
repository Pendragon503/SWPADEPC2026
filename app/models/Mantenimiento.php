<?php
/**
 * EquipRent - Órdenes de mantenimiento (RF-12) y daños de los equipos.
 *
 * Abrir una orden: el equipo pasa a Mantenimiento y sus daños pendientes a En reparación.
 * Finalizarla: el equipo vuelve a Disponible y sus daños quedan Resueltos.
 * Cancelarla: los daños vuelven a Reportado y el equipo a En revisión (o a Disponible
 * si no tenía daños). Cada cambio va en una transacción; la bitácora del equipo la
 * llena el trigger con @app_motivo (Equipo::cambiarEstado).
 * La tabla dano no tiene el id de la orden: los daños se ligan por equipo.
 */
declare(strict_types=1);

class Mantenimiento
{
    public const TIPOS = ['Preventivo', 'Correctivo'];
    public const ESTADOS_ABIERTOS = ['Diagnostico', 'EnReparacion', 'EsperandoRepuesto'];
    public const ESTADOS = ['Diagnostico', 'EnReparacion', 'EsperandoRepuesto', 'Finalizado', 'Cancelado'];
    public const ESTADOS_DANO = ['Reportado', 'EnEvaluacion', 'EnReparacion', 'Resuelto', 'Descartado'];

    /** Condición SQL de una orden abierta (alias m). */
    private const ABIERTA = "m.estado NOT IN ('Finalizado', 'Cancelado')";

    // ---------------------------------------------------------------------
    // Validaciones
    // ---------------------------------------------------------------------

    /**
     * Nueva orden. $elegibles = equiposElegibles(): solo esos equipos pueden entrar.
     * Devuelve ['campo' => 'mensaje']; vacío = válido.
     */
    public static function validar(array $datos, array $elegibles): array
    {
        $errores = [];
        if (!isset($elegibles[$datos['idEquipo']])) {
            $equipo = Equipo::buscarPorId($datos['idEquipo']);
            $errores['idEquipo'] = $equipo === null ? 'Elige el equipo.'
                : "{$equipo['codigoInventario']} está " . strip_tags(estadoBadge($equipo['estado']))
                  . ': solo entra a mantenimiento un equipo activo, Disponible o En revisión y sin otra orden abierta.';
        }
        if (!in_array($datos['tipo'], self::TIPOS, true)) {
            $errores['tipo'] = 'Elige el tipo.';
        }
        if ($datos['descripcion'] === '' || mb_strlen($datos['descripcion']) > 500) {
            $errores['descripcion'] = 'Describe el trabajo (máximo 500 caracteres).';
        }
        if (mb_strlen($datos['proveedorServicio']) > 150) {
            $errores['proveedorServicio'] = 'Máximo 150 caracteres.';
        }
        if ($error = Entrega::validarFecha($datos['fechaIngreso'])['fecha'] ?? null) {
            $errores['fechaIngreso'] = $error;
        }
        if ($datos['fechaSalidaEstimada'] !== '' && !self::esFechaHora($datos['fechaSalidaEstimada'])) {
            $errores['fechaSalidaEstimada'] = 'Fecha no válida.';
        } elseif ($datos['fechaSalidaEstimada'] !== '' && $datos['fechaSalidaEstimada'] < $datos['fechaIngreso']) {
            $errores['fechaSalidaEstimada'] = 'No puede ser anterior al ingreso.';
        }
        return $errores;
    }

    /** Cambio entre estados abiertos. Devuelve null o el mensaje. */
    public static function validarEstado(array $orden, string $estado): ?string
    {
        if (!in_array($orden['estado'], self::ESTADOS_ABIERTOS, true)) {
            return "La orden #{$orden['idMantenimiento']} ya está cerrada.";
        }
        return in_array($estado, self::ESTADOS_ABIERTOS, true) ? null : 'Elige un estado válido.';
    }

    /** Cierre: Finalizado (fecha de salida real y costo) o Cancelado. */
    public static function validarCierre(array $orden, array $datos): array
    {
        if ($error = self::validarEstado($orden, 'Diagnostico')) {
            return ['general' => $error];
        }
        if ($datos['accion'] === 'Cancelado') {
            return [];
        }
        if ($datos['accion'] !== 'Finalizado') {
            return ['general' => 'Elige finalizar o cancelar.'];
        }
        $errores = [];
        if ($error = Entrega::validarFecha($datos['fechaSalidaReal'])['fecha'] ?? null) {
            $errores['fechaSalidaReal'] = $error;
        } elseif (str_replace('T', ' ', $datos['fechaSalidaReal']) < substr($orden['fechaIngreso'], 0, 16)) {
            $errores['fechaSalidaReal'] = 'No puede ser anterior al ingreso (' . fechaCorta($orden['fechaIngreso'], true) . ').';
        }
        $costo = Entrega::numero($datos['costo']);
        if ($costo === null || $costo === false || $costo < 0) {
            $errores['costo'] = 'Escribe el costo (0 o más).';
        }
        return $errores;
    }

    private static function esFechaHora(string $valor): bool
    {
        $fecha = DateTime::createFromFormat('Y-m-d\TH:i', $valor);
        return $fecha !== false && $fecha->format('Y-m-d\TH:i') === $valor;
    }

    // ---------------------------------------------------------------------
    // Consultas
    // ---------------------------------------------------------------------

    /**
     * Equipos que pueden entrar a mantenimiento (activos, Disponible o En revisión, sin
     * orden abierta), indexados por id, con sus daños pendientes y sus contratos
     * Pendientes o Aprobados próximos (para avisar que quizá haya que reprogramarlos).
     */
    public static function equiposElegibles(): array
    {
        return Database::conexion()->query(
            "SELECT e.idEquipo, e.idEquipo AS id, e.codigoInventario, e.nombre, e.estado,
                    (SELECT COUNT(*) FROM dano d
                       JOIN detalleDevolucion dd ON dd.idDetalleDevolucion = d.idDetalleDevolucion
                       JOIN detalleContrato x ON x.idDetalleContrato = dd.idDetalleContrato
                      WHERE x.idEquipo = e.idEquipo AND d.estado IN ('Reportado', 'EnEvaluacion')) AS danosPendientes,
                    (SELECT GROUP_CONCAT(CONCAT(c.numeroContrato, ' (', c.estado, ', ', DATE_FORMAT(c.fechaInicio, '%d/%m/%Y'),
                                                ' – ', DATE_FORMAT(c.fechaFin, '%d/%m/%Y'), ')') ORDER BY c.fechaInicio SEPARATOR '; ')
                       FROM detalleContrato dc JOIN contrato c ON c.idContrato = dc.idContrato
                      WHERE dc.idEquipo = e.idEquipo AND c.estado IN ('Pendiente', 'Aprobado') AND c.fechaFin >= CURDATE()) AS contratosProximos
               FROM equipo e
              WHERE e.activo = TRUE AND e.estado IN ('Disponible', 'Revision')
                AND NOT EXISTS (SELECT 1 FROM mantenimiento m WHERE m.idEquipo = e.idEquipo AND " . self::ABIERTA . ")
              ORDER BY e.estado = 'Revision' DESC, e.codigoInventario"
        )->fetchAll(PDO::FETCH_UNIQUE);
    }

    /**
     * WHERE del listado: equipo (nombre o código), tipo, estado ('abiertas' = las tres
     * abiertas) e idEquipo (opcional, para el historial de un equipo).
     */
    private static function condiciones(array $filtros): array
    {
        $condiciones = [];
        $parametros = [];
        if (!empty($filtros['idEquipo'])) {
            $condiciones[] = 'm.idEquipo = ?';
            $parametros[] = $filtros['idEquipo'];
        }
        if ($filtros['equipo'] !== '') {
            $condiciones[] = '(e.nombre LIKE ? OR e.codigoInventario LIKE ?)';
            array_push($parametros, "%{$filtros['equipo']}%", "%{$filtros['equipo']}%");
        }
        if ($filtros['tipo'] !== '') {
            $condiciones[] = 'm.tipo = ?';
            $parametros[] = $filtros['tipo'];
        }
        if ($filtros['estado'] === 'abiertas') {
            $condiciones[] = self::ABIERTA;
        } elseif ($filtros['estado'] !== '') {
            $condiciones[] = 'm.estado = ?';
            $parametros[] = $filtros['estado'];
        }
        return [$condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '', $parametros];
    }

    public static function contar(array $filtros): int
    {
        [$where, $parametros] = self::condiciones($filtros);
        $sentencia = Database::conexion()->prepare("SELECT COUNT(*) FROM mantenimiento m JOIN equipo e ON e.idEquipo = m.idEquipo $where");
        $sentencia->execute($parametros);
        return (int) $sentencia->fetchColumn();
    }

    /** Órdenes: las abiertas primero, luego de la más reciente a la más antigua. */
    public static function listar(array $filtros, int $limite, int $desplazamiento): array
    {
        [$where, $parametros] = self::condiciones($filtros);
        $sentencia = Database::conexion()->prepare(
            "SELECT m.*, e.codigoInventario, e.nombre, CONCAT(u.nombres, ' ', u.apellidos) AS registradoPor
               FROM mantenimiento m
               JOIN equipo e ON e.idEquipo = m.idEquipo
               JOIN usuario u ON u.idUsuario = m.idRegistradoPor
               $where
              ORDER BY " . self::ABIERTA . ' DESC, m.fechaIngreso DESC, m.idMantenimiento DESC
              LIMIT ' . $limite . ' OFFSET ' . $desplazamiento
        );
        $sentencia->execute($parametros);
        return $sentencia->fetchAll();
    }

    public static function buscarPorId(int $id): ?array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT m.*, e.codigoInventario FROM mantenimiento m JOIN equipo e ON e.idEquipo = m.idEquipo WHERE m.idMantenimiento = ?'
        );
        $sentencia->execute([$id]);
        return $sentencia->fetch() ?: null;
    }

    /** Daños registrados en las devoluciones, del más reciente; $estado '' = todos; $idEquipo 0 = todos. */
    public static function danos(string $estado, int $idEquipo = 0): array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT d.*, e.idEquipo, e.codigoInventario, e.nombre, e.estado AS estadoEquipo,
                    c.idContrato, c.numeroContrato, dv.fechaDevolucion
               FROM dano d
               JOIN detalleDevolucion dd ON dd.idDetalleDevolucion = d.idDetalleDevolucion
               JOIN devolucion dv ON dv.idDevolucion = dd.idDevolucion
               JOIN detalleContrato dc ON dc.idDetalleContrato = dd.idDetalleContrato
               JOIN contrato c ON c.idContrato = dc.idContrato
               JOIN equipo e ON e.idEquipo = dc.idEquipo
              WHERE (? = '' OR d.estado = ?) AND (? = 0 OR e.idEquipo = ?)
              ORDER BY d.fechaRegistro DESC, d.idDano DESC"
        );
        $sentencia->execute([$estado, $estado, $idEquipo, $idEquipo]);
        return $sentencia->fetchAll();
    }

    // ---------------------------------------------------------------------
    // Cambios (cada uno en UNA transacción)
    // ---------------------------------------------------------------------

    /** Abre la orden: equipo a Mantenimiento y sus daños pendientes a En reparación. Devuelve el id. */
    public static function abrir(array $datos, int $idUsuario): int
    {
        return self::enTransaccion(function (PDO $conexion) use ($datos, $idUsuario): int {
            $conexion->prepare(
                "INSERT INTO mantenimiento (tipo, descripcion, fechaIngreso, fechaSalidaEstimada, proveedorServicio, idEquipo, idRegistradoPor)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([$datos['tipo'], $datos['descripcion'], str_replace('T', ' ', $datos['fechaIngreso']),
                $datos['fechaSalidaEstimada'] === '' ? null : str_replace('T', ' ', $datos['fechaSalidaEstimada']),
                Entrega::nulo($datos['proveedorServicio']), $datos['idEquipo'], $idUsuario]);
            $id = (int) $conexion->lastInsertId();
            Equipo::cambiarEstado($datos['idEquipo'], 'Mantenimiento', "Orden de mantenimiento #$id ({$datos['tipo']})");
            self::cambiarDanos($datos['idEquipo'], ['Reportado', 'EnEvaluacion'], 'EnReparacion');
            return $id;
        });
    }

    /** Avanza entre estados abiertos; false si la orden ya se había cerrado. */
    public static function cambiarEstado(array $orden, string $estado): bool
    {
        $sentencia = Database::conexion()->prepare(
            'UPDATE mantenimiento m SET m.estado = ? WHERE m.idMantenimiento = ? AND ' . self::ABIERTA
        );
        $sentencia->execute([$estado, $orden['idMantenimiento']]);
        return $sentencia->rowCount() === 1;
    }

    /**
     * Cierra la orden (ya validada). UPDATE condicionado a que siga abierta: si otro
     * usuario la cerró primero, devuelve false y no cambia nada.
     */
    public static function cerrar(array $orden, array $datos): bool
    {
        return self::enTransaccion(function (PDO $conexion) use ($orden, $datos): bool {
            $finalizar = $datos['accion'] === 'Finalizado';
            $sentencia = $conexion->prepare(
                'UPDATE mantenimiento m SET m.estado = ?, m.fechaSalidaReal = ?, m.costo = ?
                  WHERE m.idMantenimiento = ? AND ' . self::ABIERTA
            );
            $sentencia->execute([$datos['accion'], $finalizar ? str_replace('T', ' ', $datos['fechaSalidaReal']) : null,
                $finalizar ? sprintf('%.2f', Entrega::numero($datos['costo'])) : $orden['costo'], $orden['idMantenimiento']]);
            if ($sentencia->rowCount() !== 1) {
                return false;
            }

            $idEquipo = (int) $orden['idEquipo'];
            $pendientes = self::cambiarDanos($idEquipo, ['EnReparacion'], $finalizar ? 'Resuelto' : 'Reportado');
            $estado = !$finalizar && $pendientes > 0 ? 'Revision' : 'Disponible';
            Equipo::cambiarEstado($idEquipo, $estado, "Orden de mantenimiento #{$orden['idMantenimiento']} " . ($finalizar ? 'finalizada' : 'cancelada'));
            return true;
        });
    }

    /** Cambia los daños de un equipo de unos estados a otro, en UN UPDATE. Devuelve cuántos cambió. */
    private static function cambiarDanos(int $idEquipo, array $de, string $a): int
    {
        $marcas = implode(', ', array_fill(0, count($de), '?'));
        $sentencia = Database::conexion()->prepare(
            "UPDATE dano d
               JOIN detalleDevolucion dd ON dd.idDetalleDevolucion = d.idDetalleDevolucion
               JOIN detalleContrato dc ON dc.idDetalleContrato = dd.idDetalleContrato
                SET d.estado = ?
              WHERE dc.idEquipo = ? AND d.estado IN ($marcas)"
        );
        $sentencia->execute(array_merge([$a, $idEquipo], $de));
        return $sentencia->rowCount();
    }

    /** Ejecuta $trabajo en una transacción: commit si termina, rollback si lanza un error. */
    private static function enTransaccion(callable $trabajo)
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $resultado = $trabajo($conexion);
            $resultado === false ? $conexion->rollBack() : $conexion->commit();
            return $resultado;
        } catch (Throwable $e) {
            $conexion->rollBack();
            throw $e;
        }
    }
}
