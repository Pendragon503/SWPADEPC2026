<?php
/**
 * EquipRent - Inventario de equipos (RF-04, Administrador).
 *
 * Sigue el patrón del módulo de Categorías: el controlador lee los datos, llama
 * al modelo (Equipo), que valida y aplica las reglas, y decide qué mostrar.
 *
 * Rutas (app/rutas.php):
 *   GET  /admin/equipos                  -> index          listado con búsqueda, filtros y paginación
 *   POST /admin/equipos/guardar          -> guardar        crear (modal)
 *   POST /admin/equipos/actualizar       -> actualizar     editar (mismo modal)
 *   GET  /admin/equipos/detalle?id=5     -> detalle        ficha, fotos, tarifas e historial
 *   POST /admin/equipos/cambiar-estado   -> cambiarEstado  modal con motivo (bitácora)
 *   POST /admin/equipos/cambiar-activo   -> cambiarActivo  baja (modal con motivo) o reactivación
 *
 * Las fotos (FotografiaEquipoController) y las tarifas (TarifaController) tienen su
 * propio controlador, y vuelven siempre al detalle del equipo.
 */
declare(strict_types=1);

class EquipoController extends Controller
{
    private const POR_PAGINA = 10;

    /** Claves de sesión de los formularios con errores (se leen una vez y se borran). */
    private const SESION_FORMULARIO = 'formularioEquipo';

    public function __construct()
    {
        $this->requireRole('Administrador');
    }

    /**
     * GET /admin/equipos?buscar=&idCategoria=&estado=&activo=1&pagina=1
     */
    public function index(): void
    {
        $filtros = $this->leerFiltros($_GET);
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));

        $total = Equipo::contar($filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }
        $desplazamiento = ($pagina - 1) * self::POR_PAGINA;

        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);

        $this->view('equipos/index', [
            'titulo'         => 'Inventario de equipos',
            'menuActivo'     => 'Inventario',
            'equipos'        => Equipo::listar($filtros, self::POR_PAGINA, $desplazamiento),
            'indicadores'    => Indicador::resumenGeneral(),
            'categorias'     => Categoria::listar('', 1000, 0),
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

    /**
     * Filtros del listado, con valores por defecto. Se usa con $_GET (al listar) y con
     * los parámetros del campo oculto "volver" (al regresar después de un POST).
     * Solo se aceptan valores conocidos: un estado inventado se ignora.
     */
    private function leerFiltros(array $origen): array
    {
        $estado = (string) ($origen['estado'] ?? '');
        $activo = (string) ($origen['activo'] ?? '1'); // por defecto, solo los activos
        return [
            'buscar'      => trim((string) ($origen['buscar'] ?? '')),
            'idCategoria' => max(0, (int) ($origen['idCategoria'] ?? 0)),
            'estado'      => isset(Equipo::ESTADOS[$estado]) ? $estado : '',
            'activo'      => in_array($activo, ['1', '0', 'todos'], true) ? $activo : '1',
        ];
    }
}
