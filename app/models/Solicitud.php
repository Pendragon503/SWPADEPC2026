<?php
/**
 * EquipRent - Solicitud de alquiler del cliente (RF-05, RF-06, RF-07).
 *
 * Mientras el cliente arma su solicitud ("Mi solicitud"), se guarda en la SESIÓN:
 * solo las fechas y los id de los equipos, nunca precios (los precios se leen
 * siempre de la base, para que nadie pueda cambiarlos). Al enviarla se convierte
 * en un contrato Pendiente.
 *
 * Validaciones y reglas aquí; los controladores solo leen los datos y deciden qué mostrar.
 */
declare(strict_types=1);

class Solicitud
{
    /** Período máximo de un alquiler (detalleContrato.diasFacturados es SMALLINT). */
    public const MAXIMO_DIAS = 365;

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
