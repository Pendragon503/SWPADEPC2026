<?php
/**
 * EquipRent - "Mi solicitud" del cliente (RF-06, RF-07): arma la solicitud en la
 * sesión, muestra el importe y la envía como contrato Pendiente.
 *
 * Rutas (app/rutas.php):
 *   GET  /cliente/solicitud           -> ver      equipos, fechas, importe y formulario
 *   POST /cliente/solicitud/agregar   -> agregar  (idEquipo, inicio, fin)
 *   POST /cliente/solicitud/quitar    -> quitar   (idEquipo)
 *   POST /cliente/solicitud/fechas    -> fechas   cambiar el rango (inicio, fin)
 *   POST /cliente/solicitud/vaciar    -> vaciar
 *   POST /cliente/solicitud/enviar    -> enviar   (destino, observaciones)
 *
 * Controlador delgado: las reglas están en el modelo Solicitud.
 */
declare(strict_types=1);

class SolicitudController extends Controller
{
    private const SESION_FORMULARIO = 'formularioSolicitud';
    private const URL_SOLICITUD = '/cliente/solicitud';

    public function __construct()
    {
        $this->requireRole('Cliente');
    }

    /** GET /cliente/solicitud */
    public function ver(): void
    {
        $solicitud = Solicitud::actual();
        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);

        $this->view('cliente/solicitud', [
            'titulo'      => 'Mi solicitud',
            'menuActivo'  => 'solicitud',
            'solicitud'   => $solicitud,
            'importe'     => Solicitud::importe($solicitud),
            'solicitante' => Cliente::nombreVisible(Cliente::buscarPorId((int) Auth::idCliente())),
            'formulario'  => $formulario,
        ], 'cliente');
    }

    /** POST /cliente/solicitud/agregar */
    public function agregar(): void
    {
        $idEquipo = (int) ($_POST['idEquipo'] ?? 0);
        [$inicio, $fin] = $this->leerFechas();

        $errores = Solicitud::validarAgregar($idEquipo, $inicio, $fin);
        if ($errores) {
            // Un solo mensaje claro: el primero (fechas o regla general).
            flash('danger', reset($errores));
            $this->redirect($this->urlVolver());
        }

        Solicitud::agregar($idEquipo, $inicio, $fin);
        flash('success', 'Se agregó el equipo a tu solicitud del ' . periodo($inicio, $fin) . '.');
        $this->redirect(self::URL_SOLICITUD);
    }

    /** POST /cliente/solicitud/quitar */
    public function quitar(): void
    {
        Solicitud::quitar((int) ($_POST['idEquipo'] ?? 0));
        flash('success', 'Se quitó el equipo de tu solicitud.');
        $this->redirect(self::URL_SOLICITUD);
    }

    /** POST /cliente/solicitud/fechas */
    public function fechas(): void
    {
        [$inicio, $fin] = $this->leerFechas();
        $errores = Solicitud::validarFechas($inicio, $fin);
        if ($errores) {
            flash('danger', reset($errores));
            $this->redirect(self::URL_SOLICITUD);
        }

        $quitados = Solicitud::cambiarFechas($inicio, $fin);
        flash('success', 'Las fechas de tu solicitud ahora son del ' . periodo($inicio, $fin) . '.');
        if ($quitados) {
            flash('warning', 'Se quitaron porque no están libres en esas fechas: ' . implode(', ', $quitados) . '.');
        }
        $this->redirect(self::URL_SOLICITUD);
    }

    /** POST /cliente/solicitud/vaciar */
    public function vaciar(): void
    {
        Solicitud::vaciar();
        flash('success', 'Se vació tu solicitud.');
        $this->redirect(self::URL_SOLICITUD);
    }

    /**
     * POST /cliente/solicitud/enviar
     * Un segundo envío (doble clic, F5) llega con la solicitud ya vacía y no crea otro contrato.
     */
    public function enviar(): void
    {
        $solicitud = Solicitud::actual();
        $datos = [
            'destino'       => trim((string) ($_POST['destino'] ?? '')),
            'observaciones' => trim((string) ($_POST['observaciones'] ?? '')),
        ];

        $errores = Solicitud::validarEnvio($solicitud, $datos);
        if ($errores) {
            $this->volverConErrores($datos, $errores);
        }

        try {
            $numero = Solicitud::enviar((int) Auth::idCliente(), $solicitud, $datos);
        } catch (EquipoNoDisponible $e) {
            $this->volverConErrores($datos, ['general' => $e->getMessage()]);
        }

        flash('success', "Se envió tu solicitud $numero. Queda Pendiente hasta que la revise un operador; "
            . 'puedes seguirla en "Mis alquileres".');
        $this->redirect('/cliente/alquileres');
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    /** [inicio, fin] del formulario. */
    private function leerFechas(): array
    {
        return [trim((string) ($_POST['inicio'] ?? '')), trim((string) ($_POST['fin'] ?? ''))];
    }

    /** Vuelve a "Mi solicitud" con lo escrito y los errores ('general' se muestra como flash). */
    private function volverConErrores(array $datos, array $errores): void
    {
        if (isset($errores['general'])) {
            flash('danger', $errores['general']);
            unset($errores['general']);
        } else {
            flash('danger', 'Revisa los datos de la solicitud.');
        }
        $_SESSION[self::SESION_FORMULARIO] = ['valores' => $datos, 'errores' => $errores];
        $this->redirect(self::URL_SOLICITUD);
    }

    /**
     * Página desde la que se agregó el equipo (campo oculto "volver"), para volver ahí si hay
     * un error. Solo se aceptan rutas del cliente; cualquier otra cosa vuelve a "Mi solicitud".
     */
    private function urlVolver(): string
    {
        $volver = (string) ($_POST['volver'] ?? '');
        return preg_match('#^/cliente/[a-z]+(\?[A-Za-z0-9=&%_.-]*)?$#', $volver) ? $volver : self::URL_SOLICITUD;
    }
}
