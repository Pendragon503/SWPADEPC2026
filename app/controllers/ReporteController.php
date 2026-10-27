<?php
/**
 * EquipRent - Reportes (RF-14), solo para el Administrador.
 *
 * Rutas (app/rutas.php):
 *   GET  /admin/reportes  (= /admin/configuracion?tab=reportes)  -> index       "Ver reportes": generador, tarjetas e historial
 *   POST /admin/reportes/generar                                 -> generar     registra y abre el PDF o descarga el Excel
 *   GET  /admin/reportes/ver?tipo=&desde=&hasta=[&imprimir=1]     -> ver         página imprimible (vista previa o PDF)
 *   GET  /admin/reportes/descargar?tipo=&desde=&hasta=            -> descargar   CSV (Excel)
 *   GET  /admin/reportes/alquileres?desde=&hasta=&agrupar=&estado= -> alquileres  reporte de alquileres por mes/cliente/equipo/estado
 *   GET  /admin/reportes/excel?(mismos filtros)                   -> excel       ese reporte en CSV
 *   GET  /admin/reportes/equipo?id=4                              -> equipo      historial de un equipo
 * "Vista previa" y "volver a descargar" no se registran; solo "Generar" y "Descargar".
 */
declare(strict_types=1);

class ReporteController extends Controller
{
    public function __construct()
    {
        $this->requireRole('Administrador');
    }

    /** GET /admin/reportes: pestaña "Ver reportes" de Configuración. */
    public function index(): void
    {
        $this->view('reportes/panel', [
            'titulo'     => 'Configuración del sistema',
            'menuActivo' => 'Configuración',
            'rangos'     => Reporte::rangosRapidos(),
            'tarjetas'   => Reporte::tarjetas(),
            'historial'  => Reporte::historial(),
            'valores'    => ['tipo' => 'alquileres', 'desde' => date('Y-m-01'), 'hasta' => date('Y-m-t'), 'formato' => 'PDF'],
        ]);
    }

    /** POST /admin/reportes/generar: lo anota en el historial y abre el PDF o descarga el Excel. */
    public function generar(): void
    {
        $datos = [];
        foreach (['tipo', 'desde', 'hasta', 'formato'] as $campo) {
            $datos[$campo] = trim((string) ($_POST[$campo] ?? ''));
        }
        if ($datos['tipo'] === 'inventario') {
            $datos['desde'] = $datos['hasta'] = date('Y-m-d'); // corte al día
        }
        $errores = Reporte::validarGeneracion($datos);
        if ($errores) {
            flash('danger', implode(' ', $errores));
            $this->redirect('/admin/configuracion?tab=reportes');
        }

        Reporte::registrar($datos['tipo'], $datos['desde'], $datos['hasta'], $datos['formato'], (int) Auth::id());
        $this->redirect($this->urlDescarga($datos['tipo'], $datos['desde'], $datos['hasta'], $datos['formato']));
    }

    /** GET /admin/reportes/ver: el reporte en una página imprimible; con imprimir=1 abre la ventana de impresión. */
    public function ver(): void
    {
        [$tipo, $desde, $hasta] = $this->leerReporte();
        $this->view('reportes/generado', [
            'titulo'     => Reporte::TIPOS[$tipo],
            'menuActivo' => 'Configuración',
            'tipo'       => $tipo,
            'desde'      => $desde,
            'hasta'      => $hasta,
            'reporte'    => Reporte::generar($tipo, $desde, $hasta),
            'imprimir'   => ($_GET['imprimir'] ?? '') === '1',
            'urlExcel'   => $this->urlDescarga($tipo, $desde, $hasta, 'Excel'),
        ]);
    }

    /** GET /admin/reportes/descargar: el mismo reporte en CSV. */
    public function descargar(): void
    {
        [$tipo, $desde, $hasta] = $this->leerReporte();
        $reporte = Reporte::generar($tipo, $desde, $hasta);
        $this->enviarCsv("reporte-$tipo-{$desde}_$hasta.csv", ['EquipRent - ' . $reporte['titulo'], 'Período: ' . periodo($desde, $hasta)], $reporte['secciones']);
    }

    /** GET /admin/reportes/alquileres: por defecto, el mes actual agrupado por período. */
    public function alquileres(): void
    {
        $filtros = $this->leerFiltros();
        $this->view('reportes/alquileres', [
            'titulo'     => 'Reporte de alquileres',
            'menuActivo' => 'Configuración',
            'filtros'    => $filtros,
            'reporte'    => Reporte::resumen($filtros),
            'urlExcel'   => '/admin/reportes/excel?' . http_build_query($filtros),
        ]);
    }

    /** GET /admin/reportes/excel: el reporte de alquileres (mismos filtros y consulta) como CSV. */
    public function excel(): void
    {
        $filtros = $this->leerFiltros();
        $reporte = Reporte::resumen($filtros);
        [$tituloGrupo] = Reporte::AGRUPACIONES[$filtros['agrupar']];
        $filas = array_map(function (array $fila): array {
            return [$fila['grupo'], $fila['contratos'], $fila['dias'], $fila['importe']];
        }, $reporte['filas']);
        $total = $reporte['total'] === null ? null : ['Total', $reporte['total']['contratos'], $reporte['total']['dias'], $reporte['total']['importe']];

        $this->enviarCsv("reporte-alquileres-{$filtros['desde']}_{$filtros['hasta']}.csv", [
            'EquipRent - Reporte de alquileres por ' . mb_strtolower($tituloGrupo),
            'Período: ' . periodo($filtros['desde'], $filtros['hasta']) . ' · ' . ($filtros['estado'] === '' ? 'Sin Rechazados ni Cancelados' : 'Solo ' . $filtros['estado']),
        ], [['titulo' => '', 'columnas' => [[$tituloGrupo], ['Contratos'], ['Días-equipo'], ['Importe']], 'filas' => $filas, 'total' => $total]]);
    }

    /** GET /admin/reportes/equipo?id=4: contratos, entregas, devoluciones, daños, mantenimientos y bitácora. */
    public function equipo(): void
    {
        $idEquipo = (int) ($_GET['id'] ?? 0);
        $equipo = $idEquipo > 0 ? Equipo::buscarPorId($idEquipo) : null;

        $this->view('reportes/equipo', [
            'titulo'         => 'Historial de equipo',
            'menuActivo'     => 'Configuración',
            'equipos'        => Reporte::equipos(),
            'equipo'         => $equipo,
            'contratos'      => $equipo ? Reporte::contratosDeEquipo($idEquipo) : [],
            'danos'          => $equipo ? Mantenimiento::danos('', $idEquipo) : [],
            'mantenimientos' => $equipo ? Mantenimiento::listar(['idEquipo' => $idEquipo, 'equipo' => '', 'tipo' => '', 'estado' => ''], 100, 0) : [],
            'bitacora'       => $equipo ? Equipo::historialEstados($idEquipo) : [],
        ]);
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    /**
     * Envía un CSV que Excel abre bien: UTF-8 con BOM (tildes y ñ) y separador ";"
     * (el separador de lista de Windows en español, es-SV). Primero las líneas de
     * encabezado y después cada sección con sus columnas, filas y total.
     */
    private function enviarCsv(string $archivo, array $encabezado, array $secciones): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$archivo\"");
        $csv = fopen('php://output', 'w');
        fwrite($csv, "\xEF\xBB\xBF"); // BOM de UTF-8
        foreach ($encabezado as $linea) {
            fputcsv($csv, [$linea], ';');
        }
        foreach ($secciones as $seccion) {
            fputcsv($csv, [], ';');
            if ($seccion['titulo'] !== '') {
                fputcsv($csv, [$seccion['titulo']], ';');
            }
            fputcsv($csv, array_column($seccion['columnas'], 0), ';');
            foreach ($seccion['filas'] as $fila) {
                fputcsv($csv, $fila, ';');
            }
            if ($seccion['total'] !== null) {
                fputcsv($csv, $seccion['total'], ';');
            }
        }
        fclose($csv);
    }

    /** Ruta para abrir (PDF: página con la ventana de impresión) o descargar (Excel) un reporte. */
    private function urlDescarga(string $tipo, string $desde, string $hasta, string $formato): string
    {
        $consulta = http_build_query(['tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta]);
        return $formato === 'Excel' ? "/admin/reportes/descargar?$consulta" : "/admin/reportes/ver?$consulta&imprimir=1";
    }

    /** Tipo y rango de un reporte de la URL; lo desconocido se cambia por el valor por defecto. */
    private function leerReporte(): array
    {
        $tipo = isset(Reporte::TIPOS[$_GET['tipo'] ?? '']) ? $_GET['tipo'] : 'alquileres';
        [$desde, $hasta] = $tipo === 'inventario' ? [date('Y-m-d'), date('Y-m-d')] : $this->leerRango();
        return [$tipo, $desde, $hasta];
    }

    /** Rango de fechas de la URL; fechas inválidas -> el mes actual; al revés, se invierte. */
    private function leerRango(): array
    {
        $desde = esFecha($_GET['desde'] ?? '') ? $_GET['desde'] : date('Y-m-01');
        $hasta = esFecha($_GET['hasta'] ?? '') ? $_GET['hasta'] : date('Y-m-t');
        return $desde > $hasta ? [$hasta, $desde] : [$desde, $hasta];
    }

    /** Filtros del reporte de alquileres (los usan la página y el CSV). */
    private function leerFiltros(): array
    {
        [$desde, $hasta] = $this->leerRango();
        return [
            'desde'   => $desde,
            'hasta'   => $hasta,
            'agrupar' => isset(Reporte::AGRUPACIONES[$_GET['agrupar'] ?? '']) ? $_GET['agrupar'] : 'periodo',
            'estado'  => in_array($_GET['estado'] ?? '', Contrato::ESTADOS, true) ? $_GET['estado'] : '',
        ];
    }
}
