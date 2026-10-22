<?php
/**
 * EquipRent - Gestión de clientes (RF-03), para Administrador y Operador.
 *
 * Se llama GestionClienteController porque ClienteController es el panel del
 * rol Cliente. Sigue el patrón de Categorías e Inventario: el controlador lee los
 * datos, llama al modelo (Cliente), que valida y aplica las reglas, y decide qué mostrar.
 *
 * Rutas (app/rutas.php):
 *   GET  /clientes                 -> index          listado con búsqueda, filtros y paginación
 *   POST /clientes/guardar         -> guardar        crear usuario + cliente (modal)
 *   POST /clientes/actualizar      -> actualizar     editar (mismo modal)
 *   POST /clientes/cambiar-estado  -> cambiarEstado  activar / desactivar el usuario
 */
declare(strict_types=1);

class GestionClienteController extends Controller
{
    private const POR_PAGINA = 10;
    private const SESION_FORMULARIO = 'formularioCliente';

    public function __construct()
    {
        // RF-03: el administrador y el operador gestionan clientes.
        $this->requireRole('Administrador', 'Operador');
    }

    /**
     * GET /clientes?buscar=&tipoPersona=&activo=1&pagina=1
     */
    public function index(): void
    {
        $filtros = $this->leerFiltros($_GET);
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));

        $total = Cliente::contar($filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }
        $desplazamiento = ($pagina - 1) * self::POR_PAGINA;

        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);

        $this->view('clientes/index', [
            'titulo'         => 'Clientes',
            'menuActivo'     => 'Clientes',
            'clientes'       => Cliente::listar($filtros, self::POR_PAGINA, $desplazamiento),
            'filtros'        => $filtros,
            'pagina'         => $pagina,
            'totalPaginas'   => $totalPaginas,
            'total'          => $total,
            'desplazamiento' => $desplazamiento,
            'formulario'     => $formulario,
        ]);
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    /** Filtros del listado con valores por defecto; los valores desconocidos se ignoran. */
    private function leerFiltros(array $origen): array
    {
        $tipo = (string) ($origen['tipoPersona'] ?? '');
        $activo = (string) ($origen['activo'] ?? '1');
        return [
            'buscar'      => trim((string) ($origen['buscar'] ?? '')),
            'tipoPersona' => isset(Cliente::TIPOS[$tipo]) ? $tipo : '',
            'activo'      => in_array($activo, ['1', '0', 'todos'], true) ? $activo : '1',
        ];
    }
}
