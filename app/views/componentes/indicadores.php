<?php
/**
 * Fila de tarjetas de indicadores de los paneles. Cada tarjeta lleva a su módulo.
 * Antes de incluirla, la vista define:
 *   $tarjetas  [[etiqueta, valor, icono, color, nota, color de la nota, ruta], ...]
 *   $columnas  clases de columna de cada tarjeta, por ejemplo 'col-6 col-md-4 col-xxl-2'
 */
?>
<div class="row g-3 mb-4">
    <?php foreach ($tarjetas as [$etiqueta, $valor, $icono, $color, $nota, $colorNota, $ruta]): ?>
        <div class="<?= e($columnas) ?>">
            <a class="indicador indicador-enlace" href="<?= e(url($ruta)) ?>">
                <div class="indicador-cabecera">
                    <span class="indicador-etiqueta"><?= e($etiqueta) ?></span>
                    <span class="indicador-icono icono-<?= e($color) ?>"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i></span>
                </div>
                <div class="indicador-pie">
                    <span class="indicador-valor"><?= e($valor) ?></span>
                    <span class="indicador-nota nota-<?= e($colorNota) ?>"><?= e($nota) ?></span>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
