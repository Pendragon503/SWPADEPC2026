<?php
/**
 * Un reporte de "Ver reportes" en pantalla (vista previa). "Descargar PDF" baja el
 * archivo .pdf (ReporteController::pdf) y "Descargar Excel", el CSV.
 *
 * @var string $tipo, $desde, $hasta
 * @var array  $reporte   Reporte::generar(): ['titulo', 'secciones']
 * @var string $urlExcel  el mismo reporte en CSV
 * @var string $urlPdf    el mismo reporte en PDF
 */
$detalleImpresion = $reporte['titulo'] . ' · ' . ($tipo === 'inventario' ? 'corte al ' . fechaCorta($desde) : 'del ' . periodo($desde, $hasta));
require RAIZ . '/app/views/reportes/_encabezadoImpresion.php';
?>
<a href="<?= e(url('/admin/configuracion?tab=reportes')) ?>" class="enlace-volver no-imprimir"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Ver reportes</a>

<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo"><?= e($reporte['titulo']) ?></h2>
        <p class="encabezado-pagina-texto"><?= e($tipo === 'inventario' ? 'Corte al ' . fechaCorta($desde) : 'Período: ' . periodo($desde, $hasta)) ?></p>
    </div>
    <div class="acciones-pagina no-imprimir align-items-start">
        <?php require RAIZ . '/app/views/reportes/_descarga.php'; ?>
    </div>
</div>

<?php require RAIZ . '/app/views/reportes/_secciones.php'; ?>
