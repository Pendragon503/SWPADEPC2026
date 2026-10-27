<?php
/**
 * EquipRent - Contratos para Administrador y Operador: solicitudes pendientes con
 * aprobar y rechazar (RF-06), búsqueda con filtros y detalle (RF-08). El detalle es
 * la base de las entregas y devoluciones (parte 2 de Operación).
 *
 * Rutas (app/rutas.php):
 *   GET  /solicitudes?pagina= (= /contratos?tab=solicitudes)               -> solicitudes
 *   POST /contratos/aprobar     (idContrato, volver)                       -> aprobar
 *   POST /contratos/rechazar    (idContrato, motivoRechazo, volver)        -> rechazar
 *   GET  /contratos?tab=todos|solicitudes&numero=&cliente=&...&pagina=     -> index (Alquileres)
 *   GET  /contratos/detalle?id=9                                           -> detalle
 */
declare(strict_types=1);

class ContratoController extends Controller
{
    private const POR_PAGINA = 10;
    private const SESION_RECHAZO = 'formularioRechazo';

    public function __construct()
    {
        $this->requireRole('Administrador', 'Operador');
    }

    /** GET /solicitudes: las Pendientes, de la más antigua a la más reciente (se atienden en orden). */
    public function solicitudes(): void
    {
        $filtros = ['estado' => 'Pendiente'];
        $total = Contrato::contar($filtros);
        $paginas = paginacion($total, (int) ($_GET['pagina'] ?? 1), self::POR_PAGINA);

        $this->view('contratos/solicitudes', $paginas + [
            'titulo'            => 'Alquileres',
            'menuActivo'        => 'Alquileres',
            'total'             => $total,
            'solicitudes'       => Contrato::buscar($filtros, 'antiguos', self::POR_PAGINA, $paginas['desplazamiento']),
            'formularioRechazo' => $this->tomarFormularioRechazo(),
        ]);
    }

    /**
     * POST /contratos/aprobar
     * Contrato::aprobar() vuelve a revisar la disponibilidad de cada equipo y, con un
     * UPDATE condicionado a Pendiente, impide que dos usuarios la gestionen a la vez.
     */
    public function aprobar(): void
    {
        $contrato = $this->contratoDelFormulario();
        $error = Contrato::validarAprobacion($contrato) ?? Contrato::aprobar($contrato, (int) Auth::id());

        if ($error !== null) {
            flash('danger', $error);
        } else {
            flash('success', "Se aprobó {$contrato['numeroContrato']} de {$contrato['cliente']}. Ya se puede programar su entrega.");
        }
        $this->redirect($this->urlVolver($contrato));
    }

    /** POST /contratos/rechazar: con motivo obligatorio, que el cliente ve en "Mis alquileres". */
    public function rechazar(): void
    {
        $contrato = $this->contratoDelFormulario();
        $motivo = trim((string) ($_POST['motivoRechazo'] ?? ''));

        $error = Contrato::validarGestion($contrato);
        if ($error === null) {
            $errores = Contrato::validarMotivoRechazo($motivo);
            if ($errores) {
                // Se reabre el modal con lo escrito y el error (data-abrir-al-cargar).
                $_SESSION[self::SESION_RECHAZO] = [
                    'campos'  => ['idContrato' => $contrato['idContrato'], 'numeroContrato' => $contrato['numeroContrato'], 'motivoRechazo' => $motivo],
                    'errores' => $errores,
                ];
                flash('danger', 'Revisa los datos del formulario.');
                $this->redirect($this->urlVolver($contrato));
            }
            $error = Contrato::rechazar($contrato, (int) Auth::id(), $motivo);
        }

        if ($error !== null) {
            flash('danger', $error);
        } else {
            flash('success', "Se rechazó {$contrato['numeroContrato']}. Sus equipos quedaron libres en esas fechas.");
        }
        $this->redirect($this->urlVolver($contrato));
    }

    /** GET /contratos */
    public function index(): void
    {
        // Alquileres tiene dos pestañas: Todos (esta lista) y Solicitudes pendientes.
        if (pestanaActual('alquileres') === 'solicitudes') {
            $this->solicitudes();
            return;
        }
        $filtros = $this->leerFiltros($_GET);
        $total = Contrato::contar($filtros);
        $paginas = paginacion($total, (int) ($_GET['pagina'] ?? 1), self::POR_PAGINA);

        $this->view('contratos/index', $paginas + [
            'titulo'     => 'Alquileres',
            'menuActivo' => 'Alquileres',
            'filtros'    => $filtros,
            'total'      => $total,
            'contratos'  => Contrato::buscar($filtros, 'recientes', self::POR_PAGINA, $paginas['desplazamiento']),
        ]);
    }

    /** GET /contratos/detalle?id=9 */
    public function detalle(): void
    {
        $contrato = Contrato::detalle((int) ($_GET['id'] ?? 0));
        if ($contrato === null) {
            throw new ErrorHttp('Contrato no encontrado', 404);
        }

        $importe = Contrato::importe($contrato);
        $seguimiento = Contrato::seguimiento($contrato, $importe['lineas']);
        $this->view('contratos/detalle', [
            'titulo'            => 'Contrato ' . $contrato['numeroContrato'],
            'menuActivo'        => 'Alquileres',
            'contrato'          => $contrato,
            'pasos'             => Contrato::pasosProgreso($contrato),
            'importe'           => $importe,
            'seguimiento'       => $seguimiento,
            'liquidacion'       => Contrato::liquidacion($contrato),
            'puedeGestionar'    => Contrato::validarGestion($contrato) === null,
            'formularioRechazo' => $this->tomarFormularioRechazo(),
            'entrega'           => $seguimiento['entregar'] ? $this->datosEntrega($contrato, $importe['lineas']) : null,
        ]);
    }

    /**
     * Datos del modal "Formulario de entrega" (OperacionController::guardarEntrega): los
     * equipos que pueden salir, el código de la siguiente entrega (solo para mostrar) y lo
     * escrito con errores. Se abre solo con ?entregar=1 o si el último envío tuvo errores.
     */
    private function datosEntrega(array $contrato, array $lineas): array
    {
        $formulario = $_SESSION[OperacionController::SESION_ENTREGA] ?? null;
        unset($_SESSION[OperacionController::SESION_ENTREGA]);
        if (($formulario['idContrato'] ?? null) !== (int) $contrato['idContrato']) {
            $formulario = null; // eran de otro contrato
        }
        return [
            'pendientes' => Entrega::pendientes($lineas),
            'codigo'     => codigo('ENT', siguienteId('entrega'), date('Y-m-d')),
            'formulario' => $formulario,
            'abrir'      => $formulario !== null || ($_GET['entregar'] ?? '') === '1',
        ];
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    /** El contrato del campo oculto idContrato, o 404 si no existe. */
    private function contratoDelFormulario(): array
    {
        $contrato = Contrato::detalle((int) ($_POST['idContrato'] ?? 0));
        if ($contrato === null) {
            throw new ErrorHttp('Contrato no encontrado', 404);
        }
        return $contrato;
    }

    /** Después de aprobar o rechazar se vuelve a donde se hizo: el detalle o la lista de solicitudes. */
    private function urlVolver(array $contrato): string
    {
        return ($_POST['volver'] ?? '') === 'detalle' ? '/contratos/detalle?id=' . $contrato['idContrato'] : '/contratos?tab=solicitudes';
    }

    /** Modal de rechazo con errores del último envío (o null); se lee una sola vez. */
    private function tomarFormularioRechazo(): ?array
    {
        $formulario = $_SESSION[self::SESION_RECHAZO] ?? null;
        unset($_SESSION[self::SESION_RECHAZO]);
        return $formulario;
    }

    /** Filtros del listado; los valores desconocidos (estado o fecha inválidos) se ignoran. */
    private function leerFiltros(array $origen): array
    {
        $texto = function (string $campo) use ($origen): string {
            return trim((string) ($origen[$campo] ?? ''));
        };
        $fecha = function (string $campo) use ($texto): string {
            return esFecha($texto($campo)) ? $texto($campo) : '';
        };
        return [
            'numero'  => $texto('numero'),
            'cliente' => $texto('cliente'),
            'equipo'  => $texto('equipo'),
            'estado'  => in_array($texto('estado'), Contrato::ESTADOS, true) ? $texto('estado') : '',
            'desde'   => $fecha('desde'),
            'hasta'   => $fecha('hasta'),
        ];
    }
}
