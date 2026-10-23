<?php
/**
 * EquipRent - Catálogo para el cliente (RF-04 consulta, RF-05).
 *
 * Rutas (app/rutas.php):
 *   GET /cliente/catalogo?buscar=&idCategoria=&pagina=   -> index
 *   GET /cliente/equipo?id=5&mes=2026-11                 -> equipo  (detalle y calendario)
 *
 * Controlador delgado: lee los filtros, pide los datos al modelo Catalogo y muestra la vista.
 */
declare(strict_types=1);

class CatalogoController extends Controller
{
    private const POR_PAGINA = 12; // 4 filas de 3 tarjetas

    public function __construct()
    {
        $this->requireRole('Cliente');
    }

    /** GET /cliente/catalogo */
    public function index(): void
    {
        $filtros = [
            'buscar'      => trim((string) ($_GET['buscar'] ?? '')),
            'idCategoria' => max(0, (int) ($_GET['idCategoria'] ?? 0)),
        ];
        $total = Catalogo::contar($filtros);
        $paginas = paginacion($total, (int) ($_GET['pagina'] ?? 1), self::POR_PAGINA);
        $categorias = Catalogo::categoriasConTotal();

        $this->view('cliente/catalogo', $paginas + [
            'titulo'           => 'Catálogo de equipos',
            'menuActivo'       => 'catalogo',
            'filtros'          => $filtros,
            'total'            => $total,
            'equipos'          => Catalogo::listar($filtros, self::POR_PAGINA, $paginas['desplazamiento']),
            'categorias'       => $categorias,
            'totalAlquilables' => array_sum(array_column($categorias, 'total')), // "Todas (N)"
        ], 'cliente');
    }

    /** GET /cliente/equipo?id=5&mes=AAAA-MM */
    public function equipo(): void
    {
        $idEquipo = (int) ($_GET['id'] ?? 0);
        $equipo = Catalogo::equipo($idEquipo);
        if ($equipo === null) {
            throw new ErrorHttp('Equipo no encontrado', 404);
        }

        $ocupadas = Catalogo::fechasOcupadas($idEquipo);
        $tarifas = array_filter(Tarifa::listarPorEquipo($idEquipo), function (array $tarifa): bool {
            return $tarifa['situacion'] === 'Vigente';
        });

        $this->view('cliente/equipo', [
            'titulo'          => $equipo['nombre'],
            'menuActivo'      => 'catalogo',
            'equipo'          => $equipo,
            'fotos'           => FotografiaEquipo::listarPorEquipo($idEquipo),
            'especificaciones' => especificaciones($equipo['caracteristicasTecnicas']),
            'tarifas'         => $tarifas,
            'ocupadas'        => $ocupadas,
            'calendario'      => calendarioMes((string) ($_GET['mes'] ?? ''), $ocupadas),
        ], 'cliente');
    }
}
