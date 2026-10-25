<?php
/**
 * EquipRent - "Mis alquileres" del cliente (RF-08).
 *
 * Rutas (app/rutas.php):
 *   GET  /cliente/alquileres?estado=&pagina=   -> index     contratos del cliente
 *   GET  /cliente/alquiler?id=9                -> detalle   equipos, período, importes y estado
 *   POST /cliente/alquiler/cancelar            -> cancelar  solo si está Pendiente (idContrato)
 *
 * Todo se filtra con Auth::idCliente(): el id que llega por la URL o el formulario
 * nunca alcanza para ver o cancelar un contrato de otro cliente.
 */
declare(strict_types=1);

class MisAlquileresController extends Controller
{
    private const POR_PAGINA = 10;

    public function __construct()
    {
        $this->requireRole('Cliente');
    }

    /** GET /cliente/alquileres */
    public function index(): void
    {
        $idCliente = (int) Auth::idCliente();
        $estado = in_array($_GET['estado'] ?? '', Contrato::ESTADOS, true) ? $_GET['estado'] : '';
        $filtros = ['idCliente' => $idCliente, 'estado' => $estado];
        $total = Contrato::contar($filtros);
        $paginas = paginacion($total, (int) ($_GET['pagina'] ?? 1), self::POR_PAGINA);

        $this->view('cliente/alquileres', $paginas + [
            'titulo'     => 'Mis alquileres',
            'menuActivo' => 'alquileres',
            'estado'     => $estado,
            'total'      => $total,
            'contratos'  => Contrato::buscar($filtros, 'recientes', self::POR_PAGINA, $paginas['desplazamiento']),
        ], 'cliente');
    }

    /** GET /cliente/alquiler?id=9 */
    public function detalle(): void
    {
        $contrato = Contrato::detalle((int) ($_GET['id'] ?? 0), (int) Auth::idCliente());
        if ($contrato === null) {
            throw new ErrorHttp('Alquiler no encontrado', 404);
        }

        $this->view('cliente/alquiler', [
            'titulo'     => 'Alquiler ' . $contrato['numeroContrato'],
            'menuActivo' => 'alquileres',
            'contrato'   => $contrato,
            'pasos'      => Contrato::pasosProgreso($contrato),
            'importe'    => Contrato::importe($contrato),
            'puedeCancelar' => Contrato::validarCancelacion($contrato) === null,
        ], 'cliente');
    }

    /** POST /cliente/alquiler/cancelar */
    public function cancelar(): void
    {
        $idContrato = (int) ($_POST['idContrato'] ?? 0);
        $contrato = Contrato::detalle($idContrato, (int) Auth::idCliente());
        if ($contrato === null) {
            throw new ErrorHttp('Alquiler no encontrado', 404);
        }

        $error = Contrato::validarCancelacion($contrato);
        if ($error === null && !Contrato::cancelar($idContrato, (int) Auth::idCliente())) {
            // Cambió de estado entre que se abrió la página y se envió (por ejemplo, ya se aprobó).
            $error = 'No se pudo cancelar: la solicitud ya no está Pendiente.';
        }

        if ($error !== null) {
            flash('danger', $error);
        } else {
            flash('success', "Se canceló la solicitud {$contrato['numeroContrato']}. Los equipos quedaron libres en esas fechas.");
        }
        $this->redirect('/cliente/alquiler?id=' . $idContrato);
    }
}
