<?php
/**
 * EquipRent - Contratos para Administrador y Operador (RF-08): búsqueda con filtros
 * y detalle. El detalle es la base de las entregas y devoluciones (parte 2 de Operación).
 *
 * Rutas (app/rutas.php):
 *   GET /contratos?numero=&cliente=&equipo=&estado=&desde=&hasta=&pagina=  -> index
 *   GET /contratos/detalle?id=9                                           -> detalle
 */
declare(strict_types=1);

class ContratoController extends Controller
{
    private const POR_PAGINA = 10;

    public function __construct()
    {
        $this->requireRole('Administrador', 'Operador');
    }

    /** GET /contratos */
    public function index(): void
    {
        $filtros = $this->leerFiltros($_GET);
        $total = Contrato::contar($filtros);
        $paginas = paginacion($total, (int) ($_GET['pagina'] ?? 1), self::POR_PAGINA);

        $this->view('contratos/index', $paginas + [
            'titulo'     => 'Contratos',
            'menuActivo' => 'Contratos',
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

        $this->view('contratos/detalle', [
            'titulo'     => 'Contrato ' . $contrato['numeroContrato'],
            'menuActivo' => 'Contratos',
            'contrato'   => $contrato,
            'pasos'      => Contrato::pasosProgreso($contrato),
            'importe'    => Contrato::importe($contrato),
        ]);
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

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
