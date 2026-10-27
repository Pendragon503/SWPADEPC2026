<?php
/**
 * EquipRent - Registro de un alquiler desde el operador o el administrador (RF-06).
 * Usa las mismas reglas de disponibilidad e importe que la solicitud del cliente
 * (Catalogo::disponibles, Solicitud), pero el contrato queda Aprobado.
 *
 * Tres pasos:
 *   GET  /contratos/nuevo?inicio=&fin=&idCategoria=          -> nuevo      fechas y equipos libres (casillas)
 *   GET  /contratos/nuevo/revisar?inicio=&fin=&equipos[]=    -> revisar    importe, cliente y datos de entrega
 *   POST /contratos/registrar                                -> registrar  crea el contrato Aprobado
 * Revisar es GET porque no cambia nada: si el registro tiene errores, se vuelve a esa URL.
 */
declare(strict_types=1);

class RegistroAlquilerController extends Controller
{
    private const SESION_FORMULARIO = 'formularioRegistroAlquiler';

    public function __construct()
    {
        $this->requireRole('Administrador', 'Operador');
    }

    /** GET /contratos/nuevo: sin fechas solo muestra el formulario; con fechas válidas, los equipos libres. */
    public function nuevo(): void
    {
        $solicitud = $this->leerSeleccion($_GET);
        $idCategoria = max(0, (int) ($_GET['idCategoria'] ?? 0));
        $consultado = $solicitud['inicio'] !== '' || $solicitud['fin'] !== '';
        $errores = $consultado ? Solicitud::validarFechas($solicitud['inicio'], $solicitud['fin']) : [];

        $this->view('contratos/nuevo', [
            'titulo'      => 'Nuevo alquiler',
            'menuActivo'  => 'Alquileres',
            'solicitud'   => $solicitud,
            'idCategoria' => $idCategoria,
            'categorias'  => Catalogo::categoriasConTotal(),
            'errores'     => $errores,
            // null = todavía no se consultó; [] = no hay equipos libres en esas fechas.
            'equipos'     => $consultado && !$errores
                ? Catalogo::disponibles($solicitud['inicio'], $solicitud['fin'], $idCategoria) : null,
        ]);
    }

    /** GET /contratos/nuevo/revisar: importe de los equipos marcados y datos del contrato. */
    public function revisar(): void
    {
        $solicitud = $this->leerSeleccion($_GET);
        $this->siNoEsValidaVolver($solicitud);

        $importe = Solicitud::importe($solicitud);
        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);

        $this->view('contratos/revisar', [
            'titulo'       => 'Nuevo alquiler',
            'menuActivo'   => 'Alquileres',
            'solicitud'    => $solicitud,
            'importe'      => $importe,
            'tarifaDiaria' => array_sum(array_column($importe['lineas'], 'precioDiario')), // de todos los equipos juntos
            'clientes'     => Cliente::listarActivos(),
            'formulario'   => $formulario,
            'urlCambiar'   => $this->urlPaso('/contratos/nuevo', $solicitud),
        ]);
    }

    /**
     * POST /contratos/registrar
     * Vuelve a validar todo (pudo pasar tiempo desde la revisión). Un segundo envío del
     * mismo formulario no crea otro contrato: sus equipos ya quedaron reservados.
     */
    public function registrar(): void
    {
        $solicitud = $this->leerSeleccion($_POST);
        $this->siNoEsValidaVolver($solicitud);

        $idCliente = (int) ($_POST['idCliente'] ?? 0);
        $datos = [
            'destino'       => trim((string) ($_POST['destino'] ?? '')),
            'observaciones' => trim((string) ($_POST['observaciones'] ?? '')),
        ];
        $errores = Solicitud::validarDatosRegistro($idCliente, $datos);
        if ($errores) {
            $_SESSION[self::SESION_FORMULARIO] = ['valores' => $datos + ['idCliente' => $idCliente], 'errores' => $errores];
            flash('danger', 'Revisa los datos del formulario.');
            $this->redirect($this->urlPaso('/contratos/nuevo/revisar', $solicitud));
        }

        try {
            $contrato = Solicitud::registrar($idCliente, $solicitud, $datos, (int) Auth::id());
        } catch (EquipoNoDisponible $e) {
            flash('danger', $e->getMessage());
            $this->redirect($this->urlPaso('/contratos/nuevo', $solicitud));
        }

        flash('success', "Se registró el alquiler {$contrato['numeroContrato']}. Quedó Aprobado: ya se puede programar su entrega.");
        $this->redirect('/contratos/detalle?id=' . $contrato['idContrato']);
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    /** Fechas y equipos marcados, con la forma de Solicitud: ['inicio', 'fin', 'equipos' => [id, ...]]. */
    private function leerSeleccion(array $origen): array
    {
        $ids = array_map('intval', (array) ($origen['equipos'] ?? []));
        return [
            'inicio'  => trim((string) ($origen['inicio'] ?? '')),
            'fin'     => trim((string) ($origen['fin'] ?? '')),
            'equipos' => array_values(array_unique(array_filter($ids, function (int $id): bool {
                return $id > 0;
            }))),
        ];
    }

    /** Si las fechas o los equipos ya no sirven, vuelve al paso 1 con el mensaje. */
    private function siNoEsValidaVolver(array $solicitud): void
    {
        $errores = Solicitud::validarSeleccion($solicitud);
        if ($errores) {
            flash('danger', implode(' ', $errores));
            $this->redirect($this->urlPaso('/contratos/nuevo', $solicitud));
        }
    }

    /** URL de un paso con las fechas y los equipos marcados. */
    private function urlPaso(string $ruta, array $solicitud): string
    {
        return $ruta . '?' . http_build_query($solicitud);
    }
}
