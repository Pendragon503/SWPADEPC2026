<?php
/**
 * EquipRent - Entregas (RF-09) y devoluciones (RF-10, RF-11, daños), para
 * Administrador y Operador. Los formularios se abren desde el detalle del contrato.
 *
 * Rutas (app/rutas.php):
 *   GET  /entregas                     -> entregas            contratos con equipos por entregar
 *   GET  /entregas/registrar?id=9      -> formularioEntrega
 *   POST /entregas/guardar             -> guardarEntrega
 */
declare(strict_types=1);

class OperacionController extends Controller
{
    private const POR_PAGINA = 10;
    private const SESION_FORMULARIO = 'formularioOperacion';

    public function __construct()
    {
        $this->requireRole('Administrador', 'Operador');
    }

    /** GET /entregas */
    public function entregas(): void
    {
        $this->pendientes('entrega', 'Entregas pendientes', 'Entregas', 'inicio');
    }

    /** GET /entregas/registrar?id=9 */
    public function formularioEntrega(): void
    {
        $this->formulario('operacion/entrega', 'Registrar entrega', 'entregar');
    }

    /** POST /entregas/guardar */
    public function guardarEntrega(): void
    {
        [$contrato, $lineas, $datos] = $this->leerEnvio();
        $datos['nombreReceptor'] = trim((string) ($_POST['nombreReceptor'] ?? ''));
        $datos['documentoReceptor'] = mb_strtoupper(trim((string) ($_POST['documentoReceptor'] ?? '')));
        $datos['observaciones'] = trim((string) ($_POST['observaciones'] ?? ''));

        $this->siHayErroresVolver('/entregas/registrar', $contrato, $datos, Entrega::validar($contrato, $lineas, $datos));
        $error = Entrega::registrar($contrato, $datos, (int) Auth::id());
        if ($error !== null) {
            flash('danger', $error);
            $this->redirect('/entregas/registrar?id=' . $contrato['idContrato']);
        }

        flash('success', 'Se entregaron ' . count($datos['equipos']) . " equipo(s) de {$contrato['numeroContrato']} a {$datos['nombreReceptor']}. Quedaron Alquilados.");
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
            'titulo'     => $titulo . ' ' . $contrato['numeroContrato'],
            'menuActivo' => 'Contratos',
            'contrato'   => $contrato,
            'lineas'     => $lineas,
            'valores'    => $formulario['valores'] ?? [],
            'errores'    => $formulario['errores'] ?? [],
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
