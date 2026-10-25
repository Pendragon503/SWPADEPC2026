<?php
/**
 * Botones de descarga de un reporte (no salen en el PDF). "Descargar PDF" abre la
 * ventana de impresión (data-imprimir de app.js).
 *
 * Antes de incluirlo, la vista define:
 *   $urlExcel  ruta del CSV con los mismos filtros, o null si no hay versión Excel
 */
?>
<div class="no-imprimir text-end">
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <?php if ($urlExcel !== null): ?>
            <a href="<?= e(url($urlExcel)) ?>" class="btn btn-secondary"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Descargar Excel</a>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" data-imprimir><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Descargar PDF</button>
    </div>
    <p class="form-text mb-0 mt-1">En la ventana de impresión, elige “Guardar como PDF”.</p>
</div>
