<?php
/**
 * EquipRent - Mantenimiento (RF-12) y daños, para Administrador y Operador.
 *
 * Rutas (app/rutas.php):
 *   GET  /mantenimiento?tab=ordenes|danos&equipo=&...   -> index    órdenes + equipos en revisión (o daños)
 *   POST /mantenimiento/guardar                         -> guardar  abrir una orden (modal)
 *   POST /mantenimiento/estado                          -> estado   avanzar entre estados abiertos
 *   POST /mantenimiento/cerrar                          -> cerrar   finalizar o cancelar (modal)
 *   GET  /danos?estado= (= /mantenimiento?tab=danos)    -> danos    daños de las devoluciones
 */
declare(strict_types=1);

class MantenimientoController extends Controller
{
    private const POR_PAGINA = 10;
    private const SESION_FORMULARIO = 'formularioMantenimiento';

    public function __construct()
    {
        $this->requireRole('Administrador', 'Operador');
    }

    /** GET /mantenimiento: pestañas Órdenes (esta lista) y Daños (?tab=danos). */
    public function index(): void
    {
        if (pestanaActual('mantenimiento') === 'danos') {
            $this->danos();
            return;
        }
        $filtros = [
            'equipo' => trim((string) ($_GET['equipo'] ?? '')),
            'tipo'   => in_array($_GET['tipo'] ?? '', Mantenimiento::TIPOS, true) ? $_GET['tipo'] : '',
            'estado' => in_array($_GET['estado'] ?? '', array_merge(['abiertas'], Mantenimiento::ESTADOS), true) ? $_GET['estado'] : '',
        ];
        $total = Mantenimiento::contar($filtros);
        $paginas = paginacion($total, (int) ($_GET['pagina'] ?? 1), self::POR_PAGINA);
        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);
        $elegibles = Mantenimiento::equiposElegibles();

        $this->view('mantenimiento/index', $paginas + [
            'titulo'     => 'Mantenimiento',
            'menuActivo' => 'Mantenimiento',
            'filtros'    => $filtros,
            'total'      => $total,
            'ordenes'    => Mantenimiento::listar($filtros, self::POR_PAGINA, $paginas['desplazamiento']),
            'elegibles'    => $elegibles,
            'enRevision'   => array_filter($elegibles, function (array $equipo): bool {
                return $equipo['estado'] === 'Revision';
            }),
            'conContratos' => array_filter(array_column($elegibles, 'contratosProximos', 'codigoInventario')), // código => contratos
            'formulario'   => $formulario,
        ]);
    }

    /** POST /mantenimiento/guardar: avisa si el equipo tiene contratos próximos. */
    public function guardar(): void
    {
        $datos = [
            'idEquipo'            => (int) ($_POST['idEquipo'] ?? 0),
            'tipo'                => (string) ($_POST['tipo'] ?? ''),
            'descripcion'         => trim((string) ($_POST['descripcion'] ?? '')),
            'fechaIngreso'        => trim((string) ($_POST['fechaIngreso'] ?? '')),
            'fechaSalidaEstimada' => trim((string) ($_POST['fechaSalidaEstimada'] ?? '')),
            'proveedorServicio'   => trim((string) ($_POST['proveedorServicio'] ?? '')),
        ];
        $elegibles = Mantenimiento::equiposElegibles();
        $this->siHayErroresVolver('abrir', $datos, Mantenimiento::validar($datos, $elegibles));

        $id = Mantenimiento::abrir($datos, (int) Auth::id());
        $equipo = $elegibles[$datos['idEquipo']];
        flash('success', "Se abrió la orden #$id: {$equipo['codigoInventario']} quedó En mantenimiento.");
        if ($equipo['contratosProximos'] !== null) {
            flash('warning', "{$equipo['codigoInventario']} tiene contratos próximos: {$equipo['contratosProximos']}. Revisa si hay que reprogramarlos o rechazarlos.");
        }
        $this->redirect('/mantenimiento');
    }

    /** POST /mantenimiento/estado */
    public function estado(): void
    {
        $orden = $this->orden();
        $estado = (string) ($_POST['estado'] ?? '');
        $error = Mantenimiento::validarEstado($orden, $estado)
            ?? (Mantenimiento::cambiarEstado($orden, $estado) ? null : "La orden #{$orden['idMantenimiento']} ya se cerró.");
        flash($error === null ? 'success' : 'danger', $error ?? "La orden #{$orden['idMantenimiento']} pasó a " . strip_tags(estadoBadge($estado)) . '.');
        $this->redirect('/mantenimiento');
    }

    /** POST /mantenimiento/cerrar: Finalizado (equipo a Disponible) o Cancelado. */
    public function cerrar(): void
    {
        $orden = $this->orden();
        $datos = [
            'accion'          => (string) ($_POST['accion'] ?? ''),
            'fechaSalidaReal' => trim((string) ($_POST['fechaSalidaReal'] ?? '')),
            'costo'           => trim((string) ($_POST['costo'] ?? '')),
        ];
        $this->siHayErroresVolver('cerrar', $datos + ['idMantenimiento' => $orden['idMantenimiento']], Mantenimiento::validarCierre($orden, $datos));

        if (!Mantenimiento::cerrar($orden, $datos)) {
            flash('danger', "La orden #{$orden['idMantenimiento']} ya se cerró: otro usuario la cerró primero.");
        } else {
            $equipo = Equipo::buscarPorId((int) $orden['idEquipo']);
            flash('success', "La orden #{$orden['idMantenimiento']} quedó " . ($datos['accion'] === 'Finalizado' ? 'Finalizada' : 'Cancelada')
                . ". {$equipo['codigoInventario']} está " . strip_tags(estadoBadge($equipo['estado'])) . '.');
        }
        $this->redirect('/mantenimiento');
    }

    /** GET /danos */
    public function danos(): void
    {
        $estado = in_array($_GET['estado'] ?? '', Mantenimiento::ESTADOS_DANO, true) ? $_GET['estado'] : '';
        $this->view('mantenimiento/danos', [
            'titulo'     => 'Mantenimiento',
            'menuActivo' => 'Mantenimiento',
            'estado'     => $estado,
            'danos'      => Mantenimiento::danos($estado),
        ]);
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    private function orden(): array
    {
        $orden = Mantenimiento::buscarPorId((int) ($_POST['idMantenimiento'] ?? 0));
        if ($orden === null) {
            throw new ErrorHttp('Orden no encontrada', 404);
        }
        return $orden;
    }

    /** Si hay errores, guarda lo escrito y vuelve a la lista; el modal ($modal) se abre solo. */
    private function siHayErroresVolver(string $modal, array $datos, array $errores): void
    {
        if (!$errores) {
            return;
        }
        if (isset($errores['general'])) {
            flash('danger', $errores['general']);
        } else {
            $_SESSION[self::SESION_FORMULARIO] = ['modal' => $modal, 'valores' => $datos, 'errores' => $errores];
            flash('danger', $errores['idEquipo'] ?? 'Revisa los datos del formulario.');
        }
        $this->redirect('/mantenimiento');
    }
}
