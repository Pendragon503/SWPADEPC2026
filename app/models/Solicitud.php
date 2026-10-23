<?php
/**
 * EquipRent - Solicitud de alquiler del cliente (RF-05, RF-06, RF-07).
 *
 * Mientras el cliente arma su solicitud ("Mi solicitud"), se guarda en la SESIÓN:
 * solo las fechas y los id de los equipos, nunca precios (los precios se leen
 * siempre de la base, para que nadie pueda cambiarlos). Al enviarla se convierte
 * en un contrato Pendiente.
 *
 * El registro de alquileres del operador (RegistroAlquilerController) usa la misma
 * forma de solicitud (['inicio', 'fin', 'equipos']), sin la sesión, y las mismas
 * reglas de disponibilidad e importe; su contrato queda Aprobado.
 *
 * Validaciones y reglas aquí; los controladores solo leen los datos y deciden qué mostrar.
 */
declare(strict_types=1);

class Solicitud
{
    /** Período máximo de un alquiler (detalleContrato.diasFacturados es SMALLINT). */
    public const MAXIMO_DIAS = 365;

    /** Equipos que caben en una misma solicitud. */
    public const MAXIMO_EQUIPOS = 10;

    /** Clave de sesión de la solicitud en curso. */
    private const SESION = 'solicitud';

    /** Qué hacer cuando un equipo ya no está libre (final de mensajeOcupados). */
    private const CONSEJO_CLIENTE = 'Quítalo de tu solicitud o cambia las fechas.';
    private const CONSEJO_OPERADOR = 'Desmárcalo o elige otras fechas.';

    // ---------------------------------------------------------------------
    // La solicitud en curso (sesión)
    // ---------------------------------------------------------------------

    /** ['inicio' => 'AAAA-MM-DD' | null, 'fin' => ... | null, 'equipos' => [idEquipo, ...]] */
    public static function actual(): array
    {
        return $_SESSION[self::SESION] ?? ['inicio' => null, 'fin' => null, 'equipos' => []];
    }

    /** Cuántos equipos lleva (contador "Mi solicitud" del menú). */
    public static function cantidad(): int
    {
        return count(self::actual()['equipos']);
    }

    public static function vaciar(): void
    {
        unset($_SESSION[self::SESION]);
    }

    /** Agrega un equipo (ya validado con validarAgregar). El primero fija las fechas de la solicitud. */
    public static function agregar(int $idEquipo, string $inicio, string $fin): void
    {
        $solicitud = self::actual();
        $solicitud['inicio'] = $inicio;
        $solicitud['fin'] = $fin;
        $solicitud['equipos'][] = $idEquipo;
        $_SESSION[self::SESION] = $solicitud;
    }

    public static function quitar(int $idEquipo): void
    {
        $solicitud = self::actual();
        $solicitud['equipos'] = array_values(array_diff($solicitud['equipos'], [$idEquipo]));
        if ($solicitud['equipos']) {
            $_SESSION[self::SESION] = $solicitud;
        } else {
            self::vaciar(); // sin equipos tampoco se guardan las fechas
        }
    }

    /**
     * Cambia las fechas (ya validadas) y quita los equipos que no están libres en el
     * nuevo rango. Devuelve los nombres de los que se quitaron, para avisarle al cliente.
     */
    public static function cambiarFechas(string $inicio, string $fin): array
    {
        $solicitud = self::actual();
        $quitados = self::conflictos($solicitud['equipos'], $inicio, $fin);
        $solicitud['inicio'] = $inicio;
        $solicitud['fin'] = $fin;
        $solicitud['equipos'] = array_values(array_diff($solicitud['equipos'], array_keys($quitados)));
        $_SESSION[self::SESION] = $solicitud;
        return array_values($quitados);
    }

    // ---------------------------------------------------------------------
    // Validaciones y reglas
    // ---------------------------------------------------------------------

    /**
     * ¿Se puede agregar el equipo en esas fechas? Devuelve ['campo' => 'mensaje'];
     * vacío = sí. 'general' es un mensaje que no depende de un campo.
     */
    public static function validarAgregar(int $idEquipo, string $inicio, string $fin): array
    {
        $errores = self::validarFechas($inicio, $fin);
        if ($errores) {
            return $errores;
        }

        $solicitud = self::actual();
        if ($solicitud['equipos'] && ($solicitud['inicio'] !== $inicio || $solicitud['fin'] !== $fin)) {
            return ['general' => 'Tu solicitud es del ' . periodo($solicitud['inicio'], $solicitud['fin'])
                . '. Todos los equipos van en las mismas fechas: cámbialas en "Mi solicitud" o agrega este equipo en ese período.'];
        }
        if (in_array($idEquipo, $solicitud['equipos'], true)) {
            return ['general' => 'Ese equipo ya está en tu solicitud.'];
        }
        if (count($solicitud['equipos']) >= self::MAXIMO_EQUIPOS) {
            return ['general' => 'Puedes solicitar hasta ' . self::MAXIMO_EQUIPOS . ' equipos a la vez.'];
        }

        $equipo = Catalogo::equipo($idEquipo);
        if ($equipo === null) {
            return ['general' => 'Ese equipo no está disponible para alquilar.'];
        }
        if (self::conflictos([$idEquipo], $inicio, $fin)) {
            return ['general' => "{$equipo['nombre']} no está libre del " . periodo($inicio, $fin) . '. Revisa su calendario y elige otras fechas.'];
        }
        return [];
    }

    /**
     * Valida el envío: que haya equipos, que las fechas sigan siendo válidas (pudo pasar
     * un día desde que se armó), que todos sigan libres y los datos del formulario.
     *
     * @param array $datos ['destino', 'observaciones']
     */
    public static function validarEnvio(array $solicitud, array $datos): array
    {
        if (!$solicitud['equipos']) {
            return ['general' => 'Tu solicitud no tiene equipos. Agrega al menos uno desde el catálogo.'];
        }
        if (self::validarFechas($solicitud['inicio'], $solicitud['fin'])) {
            return ['general' => 'Las fechas de tu solicitud ya no son válidas. Elige nuevas fechas.'];
        }
        $ocupados = self::conflictos($solicitud['equipos'], $solicitud['inicio'], $solicitud['fin']);
        if ($ocupados) {
            return ['general' => self::mensajeOcupados($ocupados, $solicitud, self::CONSEJO_CLIENTE)];
        }
        return self::validarDatosEntrega($datos);
    }

    /**
     * Registro desde el operador (RF-06): valida las fechas y los equipos marcados.
     * Devuelve ['inicio' | 'fin' | 'equipos' => 'mensaje']; vacío = se puede seguir.
     */
    public static function validarSeleccion(array $solicitud): array
    {
        $errores = self::validarFechas($solicitud['inicio'], $solicitud['fin']);
        if ($errores) {
            return $errores;
        }
        if (!$solicitud['equipos']) {
            return ['equipos' => 'Marca al menos un equipo.'];
        }
        if (count($solicitud['equipos']) > self::MAXIMO_EQUIPOS) {
            return ['equipos' => 'Un contrato puede llevar hasta ' . self::MAXIMO_EQUIPOS . ' equipos.'];
        }
        $ocupados = self::conflictos($solicitud['equipos'], $solicitud['inicio'], $solicitud['fin']);
        if ($ocupados) {
            return ['equipos' => self::mensajeOcupados($ocupados, $solicitud, self::CONSEJO_OPERADOR)];
        }
        return [];
    }

    /**
     * Registro desde el operador: el cliente (debe existir y estar activo) y los datos
     * de entrega. Devuelve ['idCliente' | 'destino' | 'observaciones' => 'mensaje'].
     */
    public static function validarDatosRegistro(int $idCliente, array $datos): array
    {
        $errores = [];
        $cliente = $idCliente > 0 ? Cliente::buscarPorId($idCliente) : null;
        if ($cliente === null || !$cliente['activo']) {
            $errores['idCliente'] = 'Elige un cliente activo.';
        }
        return $errores + self::validarDatosEntrega($datos);
    }

    /** Destino (obligatorio) y observaciones (opcionales). Lo comparten el cliente y el operador. */
    private static function validarDatosEntrega(array $datos): array
    {
        foreach ([$datos['destino'], $datos['observaciones']] as $valor) {
            if (!mb_check_encoding($valor, 'UTF-8')) {
                return ['destino' => 'El texto tiene caracteres no válidos. Escríbelo de nuevo.'];
            }
        }
        $errores = [];
        if ($datos['destino'] === '') {
            $errores['destino'] = 'Escribe la dirección de la obra donde se entregarán los equipos.';
        } elseif (mb_strlen($datos['destino']) > 250) {
            $errores['destino'] = 'La dirección no puede tener más de 250 caracteres.';
        }
        if (mb_strlen($datos['observaciones']) > 500) {
            $errores['observaciones'] = 'Las observaciones no pueden tener más de 500 caracteres.';
        }
        return $errores;
    }

    // ---------------------------------------------------------------------
    // Consultas
    // ---------------------------------------------------------------------

    /**
     * De los equipos dados, los que NO se pueden alquilar en el rango, en UNA consulta:
     * ya no son alquilables (baja, mantenimiento, sin tarifa) o fn_conflictosEquipo
     * encuentra un contrato que se cruza. Devuelve [idEquipo => nombre].
     *
     * Con $idContrato (al aprobar un contrato ya registrado) ese contrato no cuenta
     * como cruce y no se exige tarifa vigente: su precio ya quedó guardado.
     */
    public static function conflictos(array $idsEquipo, string $inicio, string $fin, ?int $idContrato = null): array
    {
        if (!$idsEquipo) {
            return [];
        }
        $condicion = $idContrato === null ? Catalogo::CONDICION_ALQUILABLE : Catalogo::CONDICION_OPERATIVA;
        $marcas = implode(', ', array_fill(0, count($idsEquipo), '?')); // "?, ?, ?" uno por equipo
        $sentencia = Database::conexion()->prepare(
            "SELECT v.idEquipo, v.nombre
               FROM v_catalogoEquipo v
              WHERE v.idEquipo IN ($marcas)
                AND (NOT ($condicion) OR fn_conflictosEquipo(v.idEquipo, ?, ?, ?) > 0)"
        );
        $sentencia->execute(array_merge($idsEquipo, [$inicio, $fin, $idContrato]));
        return $sentencia->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Importe de la solicitud (RF-07), con los precios VIGENTES de la base y en una sola
     * consulta: por equipo la tarifa diaria, los días, el subtotal y el depósito sugerido;
     * y los totales. total = subtotal + depósito (es la regla ck_contrato_total).
     *
     * @return array ['dias', 'lineas' => [...], 'subtotal', 'deposito', 'total']; lineas vacío si no hay equipos
     */
    public static function importe(array $solicitud): array
    {
        $resultado = ['dias' => 0, 'lineas' => [], 'subtotal' => 0.0, 'deposito' => 0.0, 'total' => 0.0];
        if (!$solicitud['equipos']) {
            return $resultado;
        }

        $dias = self::dias($solicitud['inicio'], $solicitud['fin']);
        $marcas = implode(', ', array_fill(0, count($solicitud['equipos']), '?'));
        $sentencia = Database::conexion()->prepare(
            "SELECT v.idEquipo, v.codigoInventario, v.nombre, v.categoria, v.precioDiario,
                    COALESCE(v.depositoSugerido, 0) AS deposito
               FROM v_catalogoEquipo v
              WHERE v.idEquipo IN ($marcas) AND v.precioDiario IS NOT NULL
              ORDER BY v.nombre, v.codigoInventario"
        );
        $sentencia->execute($solicitud['equipos']);

        foreach ($sentencia->fetchAll() as $linea) {
            // round(..., 2): montos de dinero siempre con 2 decimales, igual que en la base.
            $linea['dias'] = $dias;
            $linea['subtotal'] = round($linea['precioDiario'] * $dias, 2);
            $resultado['lineas'][] = $linea;
            $resultado['subtotal'] += $linea['subtotal'];
            $resultado['deposito'] += (float) $linea['deposito'];
        }
        $resultado['dias'] = $dias;
        $resultado['subtotal'] = round($resultado['subtotal'], 2);
        $resultado['deposito'] = round($resultado['deposito'], 2);
        $resultado['total'] = round($resultado['subtotal'] + $resultado['deposito'], 2);
        return $resultado;
    }

    // ---------------------------------------------------------------------
    // Enviar
    // ---------------------------------------------------------------------

    /**
     * El cliente envía su solicitud: se registra como contrato Pendiente y, si todo
     * sale bien, la solicitud de la sesión se vacía AQUÍ MISMO: un segundo envío
     * (doble clic, F5) llega sin equipos y no crea otro contrato (RF-15).
     *
     * @return string numeroContrato
     */
    public static function enviar(int $idCliente, array $solicitud, array $datos): string
    {
        $numero = self::registrar($idCliente, $solicitud, $datos, null);
        self::vaciar();
        return $numero;
    }

    /**
     * Registra el contrato en UNA transacción:
     *   1. contrato con los importes calculados con los precios vigentes
     *   2. sus líneas en detalleContrato (un solo INSERT de varias filas)
     *   3. numeroContrato = ALQ-AAAA-00001 a partir del id
     * Sin $idGestor (lo envía el cliente) queda Pendiente. Con $idGestor (lo registra
     * un operador o administrador, RF-06) queda Aprobado, con su fecha y quién lo aprobó.
     * Si el trigger de detalleContrato encuentra un traslape (alguien reservó primero),
     * se hace rollback y se lanza EquipoNoDisponible con el equipo que ya no está libre.
     *
     * @return string numeroContrato
     */
    public static function registrar(int $idCliente, array $solicitud, array $datos, ?int $idGestor): string
    {
        $importe = self::importe($solicitud);
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $conexion->prepare(
                'INSERT INTO contrato (fechaInicio, fechaFin, destino, estado, subtotal, depositoGarantia,
                                       totalEstimado, observaciones, idCliente, idGestionadoPor, fechaAprobacion)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, IF(? IS NULL, NULL, CURRENT_TIMESTAMP))'
            )->execute([
                $solicitud['inicio'],
                $solicitud['fin'],
                $datos['destino'],
                $idGestor === null ? 'Pendiente' : 'Aprobado',
                self::monto($importe['subtotal']),
                self::monto($importe['deposito']),
                self::monto($importe['total']),
                $datos['observaciones'] === '' ? null : $datos['observaciones'],
                $idCliente,
                $idGestor,
                $idGestor,
            ]);
            $idContrato = (int) $conexion->lastInsertId();

            // Un solo INSERT con una fila "(?, ?, ?, ?)" por equipo.
            $filas = [];
            $valores = [];
            foreach ($importe['lineas'] as $linea) {
                $filas[] = '(?, ?, ?, ?, ?)';
                array_push($valores, self::monto($linea['precioDiario']), $linea['dias'], self::monto($linea['subtotal']),
                    $idContrato, $linea['idEquipo']);
            }
            $conexion->prepare(
                'INSERT INTO detalleContrato (precioDiarioAplicado, diasFacturados, subtotalLinea, idContrato, idEquipo)
                 VALUES ' . implode(', ', $filas)
            )->execute($valores);

            $numero = sprintf('ALQ-%s-%05d', date('Y'), $idContrato);
            $conexion->prepare('UPDATE contrato SET numeroContrato = ? WHERE idContrato = ?')->execute([$numero, $idContrato]);

            $conexion->commit();
        } catch (PDOException $e) {
            $conexion->rollBack();
            // 45000 = SIGNAL del trigger trg_detalleContrato_bi: otra persona reservó primero.
            if ($e->getCode() === '45000') {
                $ocupados = self::conflictos($solicitud['equipos'], $solicitud['inicio'], $solicitud['fin']);
                throw new EquipoNoDisponible(self::mensajeOcupados($ocupados, $solicitud,
                    $idGestor === null ? self::CONSEJO_CLIENTE : self::CONSEJO_OPERADOR));
            }
            throw $e;
        }
        return $numero;
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    /** "Minicargador Bobcat S70 ya no está libre del ..." para uno o varios equipos. */
    private static function mensajeOcupados(array $ocupados, array $solicitud, string $consejo): string
    {
        $nombres = $ocupados ? implode(', ', $ocupados) : 'Uno de los equipos';
        $verbo = count($ocupados) > 1 ? 'ya no están libres' : 'ya no está libre';
        return "$nombres $verbo del " . periodo($solicitud['inicio'], $solicitud['fin'])
            . ": otra persona lo reservó primero. $consejo";
    }

    /** Monto con 2 decimales como texto ("1350.00"), para que MySQL lo guarde exacto. */
    private static function monto($valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }

    // ---------------------------------------------------------------------
    // Validaciones
    // ---------------------------------------------------------------------

    /**
     * Valida un rango de fechas (RF-05): formato AAAA-MM-DD, inicio desde hoy,
     * fin igual o posterior al inicio y como máximo MAXIMO_DIAS días.
     * Devuelve ['inicio' | 'fin' => 'mensaje']; vacío = válido.
     */
    public static function validarFechas(string $inicio, string $fin): array
    {
        $errores = [];
        if (!self::esFecha($inicio)) {
            $errores['inicio'] = 'Elige la fecha de inicio.';
        } elseif ($inicio < date('Y-m-d')) {
            $errores['inicio'] = 'La fecha de inicio no puede ser anterior a hoy.';
        }

        if (!self::esFecha($fin)) {
            $errores['fin'] = 'Elige la fecha de fin.';
        } elseif (!isset($errores['inicio']) && $fin < $inicio) {
            $errores['fin'] = 'La fecha de fin no puede ser anterior a la de inicio.';
        } elseif (!isset($errores['inicio']) && self::dias($inicio, $fin) > self::MAXIMO_DIAS) {
            $errores['fin'] = 'El alquiler puede durar como máximo ' . self::MAXIMO_DIAS . ' días.';
        }
        return $errores;
    }

    /** Días que se facturan: del inicio al fin, contando los dos (3 al 5 = 3 días). */
    public static function dias(string $inicio, string $fin): int
    {
        return (int) (new DateTime($inicio))->diff(new DateTime($fin))->format('%a') + 1;
    }

    private static function esFecha(string $valor): bool
    {
        $fecha = DateTime::createFromFormat('Y-m-d', $valor);
        return $fecha !== false && $fecha->format('Y-m-d') === $valor;
    }
}
