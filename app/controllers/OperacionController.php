<?php
/**
 * EquipRent - Entregas (RF-09) y devoluciones (RF-10, RF-11, daños), para
 * Administrador y Operador. Los formularios se abren desde el detalle del contrato.
 *
 * Rutas (app/rutas.php):
 *   GET  /entregas?tab=entregas|devoluciones -> entregas  (sección "Entregas y Devoluciones")
 *   GET  /entregas/registrar?id=9      -> formularioEntrega   (lleva al detalle del contrato con el modal abierto)
 *   POST /entregas/guardar             -> guardarEntrega      (modal "Formulario de entrega")
 *   GET  /devoluciones                 -> devoluciones        contratos con equipos por recibir
 *   GET  /devoluciones/registrar?id=9  -> formularioDevolucion
 *   POST /devoluciones/guardar         -> guardarDevolucion
 */
declare(strict_types=1);

class OperacionController extends Controller
{
    private const POR_PAGINA = 10;
    private const SESION_FORMULARIO = 'formularioOperacion';

    /** Modal de entrega con errores; lo lee ContratoController::detalle(), donde vive el modal. */
    public const SESION_ENTREGA = 'formularioEntrega';

    public function __construct()
    {
        $this->requireRole('Administrador', 'Operador');
    }

    /** GET /entregas */
    public function entregas(): void
    {
        // "Entregas y Devoluciones" tiene dos pestañas: Entregas pendientes y Devoluciones pendientes.
        if (pestanaActual('operacion') === 'devoluciones') {
            $this->devoluciones();
            return;
        }
        $this->pendientes('entrega', 'Entregas pendientes', 'Entregas y Devoluciones', 'inicio');
    }

    /** GET /entregas/registrar?id=9: la entrega ahora es un modal del detalle del contrato. */
    public function formularioEntrega(): void
    {
        $this->redirect('/contratos/detalle?id=' . (int) ($_GET['id'] ?? 0) . '&entregar=1');
    }

    /**
     * POST /entregas/guardar (modal "Formulario de entrega"): un equipo o "todos los
     * pendientes". Se convierte al formato de siempre y usa Entrega::validar() y
     * registrar() sin cambiar sus reglas.
     */
    public function guardarEntrega(): void
    {
        $contrato = $this->contrato((int) ($_POST['idContrato'] ?? 0));
        $lineas = Contrato::lineas((int) $contrato['idContrato']);
        $texto = function (string $campo): string {
            return trim((string) ($_POST[$campo] ?? ''));
        };
        $eleccion = $texto('equipo');
        $campos = [
            'estadoFisico'     => $texto('estadoFisico'),
            'nivelCombustible' => $texto('nivelCombustible'),
            'lecturaHorometro' => $texto('lecturaHorometro'),
            'accesorios'       => $texto('accesorios'),
        ];
        $datos = [
            'fecha'             => $texto('fecha'),
            'nombreReceptor'    => $texto('nombreReceptor'),
            'documentoReceptor' => mb_strtoupper($texto('documentoReceptor')),
            'observaciones'     => $texto('observaciones'),
            'equipos'           => Entrega::equiposDelModal($lineas, $eleccion, $campos),
        ];
        $urlModal = '/contratos/detalle?id=' . $contrato['idContrato'] . '&entregar=1';

        $errores = Entrega::erroresDelModal(Entrega::validar($contrato, $lineas, $datos));
        if ($errores) {
            // Se reabre el modal con lo escrito y los errores.
            $_SESSION[self::SESION_ENTREGA] = [
                'idContrato' => (int) $contrato['idContrato'],
                'valores'    => ['equipo' => $eleccion] + $campos + $datos,
                'errores'    => $errores,
            ];
            // El motivo va también en el mensaje: si ya no quedan pendientes, el modal no se muestra.
            flash('danger', $errores['general'] ?? $errores['equipo'] ?? 'Revisa los datos de la entrega.');
            $this->redirect($urlModal);
        }
        $error = Entrega::registrar($contrato, $datos, (int) Auth::id());
        if ($error !== null) {
            flash('danger', $error);
            $this->redirect($urlModal);
        }

        flash('success', 'Se entregaron ' . count($datos['equipos']) . " equipo(s) de {$contrato['numeroContrato']} a {$datos['nombreReceptor']}. Quedaron Alquilados.");
        $this->redirect('/contratos/detalle?id=' . $contrato['idContrato']);
    }

    /** GET /devoluciones */
    public function devoluciones(): void
    {
        $this->pendientes('devolucion', 'Devoluciones pendientes', 'Entregas y Devoluciones', 'fin');
    }

    /** GET /devoluciones/registrar?id=9 */
    public function formularioDevolucion(): void
    {
        $this->formulario('operacion/devolucion', 'Registrar devolución', 'recibir');
    }

    /** POST /devoluciones/guardar: al terminar muestra el atraso y cuánto de la garantía se devuelve. */
    public function guardarDevolucion(): void
    {
        [$contrato, $lineas, $datos] = $this->leerEnvio();
        $this->siHayErroresVolver('/devoluciones/registrar', $contrato, $datos, Devolucion::validar($contrato, $lineas, $datos));
        $error = Devolucion::registrar($contrato, $lineas, $datos, (int) Auth::id());
        if ($error !== null) {
            flash('danger', $error);
            $this->redirect('/devoluciones/registrar?id=' . $contrato['idContrato']);
        }

        $contrato = Contrato::detalle((int) $contrato['idContrato']); // con la penalización y los daños nuevos
        $liquidacion = Contrato::liquidacion($contrato);
        flash('success', 'Se recibieron ' . count($datos['equipos']) . " equipo(s) de {$contrato['numeroContrato']}. "
            . 'Días de atraso: ' . diasAtraso($contrato['fechaFin'], $datos['fecha']) . '. '
            . ($liquidacion['saldo'] > 0 ? 'Saldo a pagar por el cliente: ' . dinero($liquidacion['saldo']) : 'Garantía a devolver: ' . dinero($liquidacion['garantia'])) . '.'
            . ($contrato['estado'] === 'Finalizado' ? ' El contrato quedó Finalizado.' : ''));
        $this->redirect('/contratos/detalle?id=' . $contrato['idContrato']);
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados (los comparten entregas y devoluciones)
    // ---------------------------------------------------------------------

    /** Lista de contratos con equipos por entregar o por recibir, los más urgentes primero ($orden). */
    private function pendientes(string $tipo, string $titulo, string $menu, string $orden): void
    {
        $filtros = ['pendiente' => $tipo];
        $total = Contrato::contar($filtros);
        $paginas = paginacion($total, (int) ($_GET['pagina'] ?? 1), self::POR_PAGINA);

        $this->view('operacion/pendientes', $paginas + [
            'titulo'     => $titulo,
            'menuActivo' => $menu,
            'tipo'       => $tipo,
            'total'      => $total,
            'contratos'  => Contrato::buscar($filtros, $orden, self::POR_PAGINA, $paginas['desplazamiento']),
        ]);
    }

    /**
     * Formulario de entrega o devolución de un contrato, con los errores del último envío.
     * Si no queda nada por $accion ('entregar' | 'recibir'), vuelve al detalle con un aviso.
     */
    private function formulario(string $vista, string $titulo, string $accion): void
    {
        $contrato = $this->contrato((int) ($_GET['id'] ?? 0));
        $lineas = Contrato::lineas((int) $contrato['idContrato']);
        if (!Contrato::seguimiento($contrato, $lineas)[$accion]) {
            flash('warning', "{$contrato['numeroContrato']} no tiene equipos por $accion.");
            $this->redirect('/contratos/detalle?id=' . $contrato['idContrato']);
        }

        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);
        if (($formulario['idContrato'] ?? null) !== (int) $contrato['idContrato']) {
            $formulario = null; // eran de otro contrato
        }

        $this->view($vista, [
            'titulo'      => $titulo . ' ' . $contrato['numeroContrato'],
            'menuActivo'  => 'Entregas y Devoluciones',
            'contrato'    => $contrato,
            'lineas'      => $lineas,
            'liquidacion' => Contrato::liquidacion($contrato), // cargos de devoluciones anteriores
            'valores'     => $formulario['valores'] ?? [],
            'errores'     => $formulario['errores'] ?? [],
        ]);
    }

    /** Contrato, líneas y datos comunes del envío (fecha y equipos marcados). */
    private function leerEnvio(): array
    {
        $contrato = $this->contrato((int) ($_POST['idContrato'] ?? 0));
        $datos = [
            'fecha'   => trim((string) ($_POST['fecha'] ?? '')),
            'equipos' => Entrega::equiposMarcados((array) ($_POST['equipos'] ?? [])),
        ];
        return [$contrato, Contrato::lineas((int) $contrato['idContrato']), $datos];
    }

    private function contrato(int $idContrato): array
    {
        $contrato = Contrato::detalle($idContrato);
        if ($contrato === null) {
            throw new ErrorHttp('Contrato no encontrado', 404);
        }
        return $contrato;
    }

    /** Si hay errores, guarda lo escrito y vuelve al formulario. */
    private function siHayErroresVolver(string $ruta, array $contrato, array $datos, array $errores): void
    {
        if (!$errores) {
            return;
        }
        $_SESSION[self::SESION_FORMULARIO] = ['idContrato' => (int) $contrato['idContrato'], 'valores' => $datos + ['todos' => $_POST['equipos'] ?? []], 'errores' => $errores];
        flash('danger', $errores['general'] ?? $errores['equipos'] ?? 'Revisa los datos del formulario.');
        $this->redirect($ruta . '?id=' . $contrato['idContrato']);
    }
}
