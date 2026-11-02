<?php
/**
 * Configuración → Ver reportes: generador, tres reportes rápidos y el historial de los
 * últimos 30 días. "Generar" y "Descargar" se anotan en el historial; "Vista previa" no.
 *
 * @var array $rangos     Reporte::rangosRapidos(): tipo => [desde, hasta]
 * @var array $tarjetas   Reporte::tarjetas()
 * @var array $historial  Reporte::historial()
 * @var array $valores    valores iniciales del generador
 */
$pestana = 'reportes';
require RAIZ . '/app/views/configuracion/_encabezado.php';

// [tipo, icono, periodicidad, formato, número, texto del número]
$rapidos = [
    ['inventario', 'bi-box-seam', 'Corte al día', 'PDF', $tarjetas['equipos'], $tarjetas['disponibles'] . ' disponibles'],
    ['fallas', 'bi-exclamation-triangle', 'Mensual', 'Excel', $tarjetas['danos'] + $tarjetas['mantenimientos'],
        $tarjetas['danos'] . ' daños y ' . $tarjetas['mantenimientos'] . ' mantenimientos'],
    ['salidas', 'bi-truck', 'Semanal', 'PDF', $tarjetas['salidas'], 'equipos entregados'],
];
$consulta = function (string $tipo, string $desde, string $hasta): string {
    return http_build_query(['tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta]);
};
?>
<div class="row g-4 mb-4">
    <div class="col-xl-5">
        <section class="panel">
            <div class="panel-cabecera panel-cabecera-linea"><h3 class="panel-titulo panel-titulo-mayus">Generar reporte</h3></div>
            <div class="panel-cuerpo">
                <form method="post" action="<?= e(url('/admin/reportes/generar')) ?>" class="d-grid gap-3" novalidate>
                    <?= csrfCampo() ?>
                    <div>
                        <label for="tipo" class="form-label">Tipo de reporte</label>
                        <select id="tipo" name="tipo" class="form-select">
                            <?php foreach (Reporte::TIPOS as $clave => $texto): ?>
                                <option value="<?= e($clave) ?>"<?= $clave === $valores['tipo'] ? ' selected' : '' ?>><?= e($texto) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label for="desde" class="form-label">Fecha inicio</label>
                            <input type="date" id="desde" name="desde" class="form-control" value="<?= e($valores['desde']) ?>">
                        </div>
                        <div class="col-6">
                            <label for="hasta" class="form-label">Fecha fin</label>
                            <input type="date" id="hasta" name="hasta" class="form-control" value="<?= e($valores['hasta']) ?>">
                        </div>
                    </div>
                    <div>
                        <span class="form-label d-block">Formato</span>
                        <?php foreach (Reporte::FORMATOS as $formato): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="formato" id="formato<?= e($formato) ?>" value="<?= e($formato) ?>"<?= $formato === $valores['formato'] ? ' checked' : '' ?>>
                                <label class="form-check-label" for="formato<?= e($formato) ?>"><?= e($formato) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="form-text m-0">"Inventario y disponibilidad" es un corte al día de hoy: no usa las fechas. El PDF se guarda desde la ventana de impresión ("Guardar como PDF").</p>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i> Generar reporte</button>
                </form>
                <p class="small mt-3 mb-0">
                    <a href="<?= e(url('/admin/reportes/alquileres')) ?>">Reporte de alquileres por cliente, equipo o estado</a> ·
                    <a href="<?= e(url('/admin/reportes/equipo')) ?>">Historial de un equipo</a>
                </p>
            </div>
        </section>
    </div>
    <div class="col-xl-7">
        <div class="d-grid gap-3">
            <?php foreach ($rapidos as [$tipo, $icono, $periodicidad, $formato, $numero, $textoNumero]): ?>
                <?php [$desde, $hasta] = $rangos[$tipo]; ?>
                <section class="panel reporte-rapido">
                    <div class="panel-cuerpo">
                        <span class="indicador-icono icono-naranja"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i></span>
                        <div class="reporte-rapido-texto">
                            <h3 class="panel-titulo"><?= e(Reporte::TIPOS[$tipo]) ?></h3>
                            <p class="texto-suave small mb-1"><?= e($periodicidad) ?> · <?= e(periodo($desde, $hasta)) ?> · <?= e($formato) ?></p>
                            <p class="mb-0"><span class="reporte-rapido-numero"><?= e($numero) ?></span> <span class="texto-suave small"><?= e($textoNumero) ?></span></p>
                        </div>
                        <div class="reporte-rapido-acciones">
                            <a href="<?= e(url('/admin/reportes/ver?' . $consulta($tipo, $desde, $hasta))) ?>" class="btn btn-secondary btn-sm"><i class="bi bi-eye" aria-hidden="true"></i> Vista previa</a>
                            <form method="post" action="<?= e(url('/admin/reportes/generar')) ?>">
                                <?= csrfCampo() ?>
                                <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
                                <input type="hidden" name="desde" value="<?= e($desde) ?>">
                                <input type="hidden" name="hasta" value="<?= e($hasta) ?>">
                                <input type="hidden" name="formato" value="<?= e($formato) ?>">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-download" aria-hidden="true"></i> Descargar <?= e($formato) ?></button>
                            </form>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<section class="panel">
    <div class="panel-cabecera panel-cabecera-linea">
        <h3 class="panel-titulo panel-titulo-mayus">Historial de reportes descargados</h3>
        <span class="texto-suave small">Últimos 30 días</span>
    </div>
    <div class="panel-cuerpo">
        <?php if (!$historial): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">Todavía no se ha descargado ningún reporte</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr><th scope="col">ID</th><th scope="col">Tipo</th><th scope="col">Rango</th><th scope="col" class="d-none d-md-table-cell">Generado por</th><th scope="col">Formato</th><th scope="col" class="col-acciones">Acción</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($historial as $reporte): ?>
                        <?php $parametros = $consulta($reporte['tipo'], $reporte['fechaInicio'], $reporte['fechaFin']); ?>
                        <tr>
                            <td><span class="codigo"><?= e(codigo('REP', (int) $reporte['idReporte'], $reporte['fechaGeneracion'])) ?></span></td>
                            <td><?= e(Reporte::TIPOS[$reporte['tipo']] ?? $reporte['tipo']) ?></td>
                            <td class="text-nowrap"><?= e(periodo($reporte['fechaInicio'], $reporte['fechaFin'])) ?></td>
                            <td class="d-none d-md-table-cell"><?= e($reporte['usuario']) ?><br><span class="texto-suave small"><?= e(fechaCorta($reporte['fechaGeneracion'], true)) ?></span></td>
                            <td><span class="estado <?= $reporte['formato'] === 'PDF' ? 'estado-rojo' : 'estado-verde' ?>"><?= e($reporte['formato']) ?></span></td>
                            <td class="col-acciones">
                                <?php // Volver a descargar regenera el reporte con los mismos parámetros (no se vuelve a anotar). ?>
                                <a class="btn btn-claro btn-sm" href="<?= e(url($reporte['formato'] === 'Excel' ? "/admin/reportes/descargar?$parametros" : "/admin/reportes/ver?$parametros&imprimir=1")) ?>">
                                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Volver a descargar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
