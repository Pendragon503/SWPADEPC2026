<?php
/**
 * EquipRent - Tarifas de un equipo (modal "Nueva tarifa" en su detalle).
 *
 * Ruta: POST /admin/equipos/tarifas/guardar -> guardar
 *
 * Las tarifas no se editan ni se borran: para cambiar un precio se crea una nueva,
 * y el modelo cierra la anterior del mismo tipo en la misma transacción.
 */
declare(strict_types=1);

class TarifaController extends Controller
{
    /** Clave de sesión del modal con errores; la lee EquipoController::detalle(). */
    public const SESION_FORMULARIO = 'formularioTarifa';

    public function __construct()
    {
        $this->requireRole('Administrador');
    }

    /** POST /admin/equipos/tarifas/guardar */
    public function guardar(): void
    {
        $idEquipo = (int) ($_POST['idEquipo'] ?? 0);
        $equipo = Equipo::buscarPorId($idEquipo);
        if ($equipo === null) {
            flash('danger', 'El equipo no existe.');
            $this->redirect('/admin/equipos');
        }

        $datos = [
            'tipoPeriodo'         => (string) ($_POST['tipoPeriodo'] ?? ''),
            'monto'               => trim((string) ($_POST['monto'] ?? '')),
            'depositoSugerido'    => trim((string) ($_POST['depositoSugerido'] ?? '')),
            'fechaInicioVigencia' => trim((string) ($_POST['fechaInicioVigencia'] ?? '')),
        ];

        $errores = Tarifa::validar($equipo, $datos);
        if ($errores) {
            $_SESSION[self::SESION_FORMULARIO] = [
                'idEquipo' => $idEquipo,
                'valores'  => $datos,
                'errores'  => $errores,
            ];
            flash('danger', 'Revisa los datos de la tarifa.');
            $this->redirect('/admin/equipos/detalle?id=' . $idEquipo . '#tarifas');
        }

        Tarifa::crear($idEquipo, $datos);
        flash('success', 'Se registró la tarifa ' . mb_strtolower(Tarifa::PERIODOS[$datos['tipoPeriodo']])
            . ' de ' . dinero($datos['monto']) . ' desde el ' . fechaCorta($datos['fechaInicioVigencia']) . '.');
        $this->redirect('/admin/equipos/detalle?id=' . $idEquipo . '#tarifas');
    }
}
