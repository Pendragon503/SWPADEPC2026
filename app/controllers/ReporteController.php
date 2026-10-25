<?php
/**
 * EquipRent - Reportes (RF-14), solo para el Administrador.
 *
 * Rutas (app/rutas.php):
 *   GET /admin/reportes?desde=&hasta=&agrupar=&estado=   -> index   alquileres agrupados con totales
 *   GET /admin/reportes/equipo?id=4                      -> equipo  historial de un equipo
 */
declare(strict_types=1);

class ReporteController extends Controller
{
    public function __construct()
    {
        $this->requireRole('Administrador');
    }

    /** GET /admin/reportes: por defecto, el mes actual agrupado por período. */
    public function index(): void
    {
        $desde = esFecha($_GET['desde'] ?? '') ? $_GET['desde'] : date('Y-m-01');
        $hasta = esFecha($_GET['hasta'] ?? '') ? $_GET['hasta'] : date('Y-m-t');
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde]; // rango al revés: se invierte
        }
        $filtros = [
            'desde'   => $desde,
            'hasta'   => $hasta,
            'agrupar' => isset(Reporte::AGRUPACIONES[$_GET['agrupar'] ?? '']) ? $_GET['agrupar'] : 'periodo',
            'estado'  => in_array($_GET['estado'] ?? '', Contrato::ESTADOS, true) ? $_GET['estado'] : '',
        ];

        $this->view('reportes/index', [
            'titulo'     => 'Reportes',
            'menuActivo' => 'Reportes',
            'filtros'    => $filtros,
            'reporte'    => Reporte::resumen($filtros),
        ]);
    }

    /** GET /admin/reportes/equipo?id=4: contratos, entregas, devoluciones, daños, mantenimientos y bitácora. */
    public function equipo(): void
    {
        $idEquipo = (int) ($_GET['id'] ?? 0);
        $equipo = $idEquipo > 0 ? Equipo::buscarPorId($idEquipo) : null;

        $this->view('reportes/equipo', [
            'titulo'         => 'Historial de equipo',
            'menuActivo'     => 'Reportes',
            'equipos'        => Reporte::equipos(),
            'equipo'         => $equipo,
            'contratos'      => $equipo ? Reporte::contratosDeEquipo($idEquipo) : [],
            'danos'          => $equipo ? Mantenimiento::danos('', $idEquipo) : [],
            'mantenimientos' => $equipo ? Mantenimiento::listar(['idEquipo' => $idEquipo, 'equipo' => '', 'tipo' => '', 'estado' => ''], 100, 0) : [],
            'bitacora'       => $equipo ? Equipo::historialEstados($idEquipo) : [],
        ]);
    }
}
