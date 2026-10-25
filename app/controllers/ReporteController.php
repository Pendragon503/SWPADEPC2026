<?php
/**
 * EquipRent - Reportes (RF-14), solo para el Administrador.
 *
 * Rutas (app/rutas.php):
 *   GET /admin/reportes?desde=&hasta=&agrupar=&estado=   -> index   alquileres agrupados con totales
 *   GET /admin/reportes/excel?(mismos filtros)           -> excel   el mismo reporte en CSV
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
        $filtros = $this->leerFiltros();
        $this->view('reportes/index', [
            'titulo'     => 'Reportes',
            'menuActivo' => 'Reportes',
            'filtros'    => $filtros,
            'reporte'    => Reporte::resumen($filtros),
            'urlExcel'   => '/admin/reportes/excel?' . http_build_query($filtros),
        ]);
    }

    /**
     * GET /admin/reportes/excel: el mismo reporte (mismos filtros y consulta) como CSV.
     * UTF-8 con BOM para que Excel muestre bien tildes y ñ; separador ";" porque es el
     * separador de lista de Windows en español (es-SV) y Excel lo abre en columnas.
     */
    public function excel(): void
    {
        $filtros = $this->leerFiltros();
        $reporte = Reporte::resumen($filtros);
        [$tituloGrupo] = Reporte::AGRUPACIONES[$filtros['agrupar']];

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"reporte-alquileres-{$filtros['desde']}_{$filtros['hasta']}.csv\"");
        $csv = fopen('php://output', 'w');
        fwrite($csv, "\xEF\xBB\xBF"); // BOM de UTF-8
        $filas = [
            ['EquipRent - Reporte de alquileres por ' . mb_strtolower($tituloGrupo)],
            ['Período: ' . periodo($filtros['desde'], $filtros['hasta']), $filtros['estado'] === '' ? 'Sin Rechazados ni Cancelados' : 'Solo ' . $filtros['estado']],
            [],
            [$tituloGrupo, 'Contratos', 'Días-equipo', 'Importe'],
        ];
        foreach ($reporte['filas'] as $fila) {
            $filas[] = [$fila['grupo'], $fila['contratos'], $fila['dias'], $fila['importe']];
        }
        if ($reporte['total'] !== null) {
            $filas[] = ['Total', $reporte['total']['contratos'], $reporte['total']['dias'], $reporte['total']['importe']];
        }
        foreach ($filas as $fila) {
            fputcsv($csv, $fila, ';');
        }
        fclose($csv);
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

    /** Filtros del reporte (los usan la página y el CSV); fechas inválidas -> mes actual. */
    private function leerFiltros(): array
    {
        $desde = esFecha($_GET['desde'] ?? '') ? $_GET['desde'] : date('Y-m-01');
        $hasta = esFecha($_GET['hasta'] ?? '') ? $_GET['hasta'] : date('Y-m-t');
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde]; // rango al revés: se invierte
        }
        return [
            'desde'   => $desde,
            'hasta'   => $hasta,
            'agrupar' => isset(Reporte::AGRUPACIONES[$_GET['agrupar'] ?? '']) ? $_GET['agrupar'] : 'periodo',
            'estado'  => in_array($_GET['estado'] ?? '', Contrato::ESTADOS, true) ? $_GET['estado'] : '',
        ];
    }
}
