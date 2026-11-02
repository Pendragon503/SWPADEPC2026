<?php
/**
 * Botones de descarga de un reporte (no salen en el PDF).
 *
 * Antes de incluirlo, la vista define:
 *   $urlExcel  ruta del CSV con los mismos filtros, o null si no hay versión Excel
 *   $urlPdf    (opcional) ruta que descarga el .pdf. Sin ella, "Descargar PDF" abre la
 *              ventana de impresión (data-imprimir de app.js).
 */
$urlPdf = $urlPdf ?? null;
?>
<div class="no-imprimir text-end">
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <?php if ($urlExcel !== null): ?>
            <a href="<?= e(url($urlExcel)) ?>" class="btn btn-secondary"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Descargar Excel</a>
        <?php endif; ?>
        <?php if ($urlPdf !== null): ?>
            <a href="<?= e(url($urlPdf)) ?>" class="btn btn-primary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Descargar PDF</a>
        <?php else: ?>
            <button type="button" class="btn btn-primary" data-imprimir><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Descargar PDF</button>
        <?php endif; ?>
    </div>
    <?php if ($urlPdf === null): ?>
        <p class="form-text mb-0 mt-1">En la ventana de impresión, elige “Guardar como PDF”.</p>
    <?php endif; ?>
</div>
