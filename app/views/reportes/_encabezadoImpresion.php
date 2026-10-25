<?php
/**
 * Encabezado que solo aparece en el PDF (impresión): nombre del sistema, qué reporte
 * es y la fecha de emisión.
 *
 * Antes de incluirlo, la vista define:
 *   $detalleImpresion  por ejemplo "Alquileres por mes · del 01/10/2026 al 31/10/2026"
 */
?>
<div class="encabezado-impresion solo-imprimir">
    <p class="encabezado-impresion-sistema">EQUIPRENT · Sistema de Alquiler de Equipos</p>
    <p class="mb-0"><?= e($detalleImpresion) ?></p>
    <p class="mb-0 small">Emitido el <?= e(date('d/m/Y H:i')) ?></p>
</div>
