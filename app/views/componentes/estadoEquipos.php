<?php
/**
 * Dona "Estado de equipos" (solo CSS, sin librerías de gráficos).
 * Se incluye desde los dashboards: necesita $indicadores = Indicador::resumenGeneral().
 *
 * @var array $indicadores
 */
$total = $indicadores['equiposTotales'];

// [texto, cantidad, variable CSS del color]
$segmentos = [
    ['Disponibles', $indicadores['disponibles'], '--er-verde'],
    ['Alquilados', $indicadores['alquilados'], '--er-naranja'],
    ['En mantenimiento', $indicadores['enMantenimiento'], '--er-azul'],
    ['Fuera de servicio', $indicadores['fueraServicio'], '--er-rojo'],
];
// Los que están en revisión no tienen indicador propio: se muestran como "Otros".
$otros = $total - $indicadores['disponibles'] - $indicadores['alquilados']
       - $indicadores['enMantenimiento'] - $indicadores['fueraServicio'];
if ($otros > 0) {
    $segmentos[] = ['Otros (en revisión)', $otros, '--er-gris'];
}

// conic-gradient: cada segmento ocupa un porcentaje del círculo.
$partes = [];
$inicio = 0.0;
foreach ($segmentos as [$texto, $cantidad, $color]) {
    if ($total > 0 && $cantidad > 0) {
        $fin = $inicio + $cantidad * 100 / $total;
        $partes[] = sprintf('var(%s) %.2f%% %.2f%%', $color, $inicio, $fin);
        $inicio = $fin;
    }
}
$fondoDona = $partes ? 'conic-gradient(' . implode(', ', $partes) . ')' : 'var(--er-borde-suave)';
?>
<div class="dona-contenedor">
    <div class="dona" style="background: <?= e($fondoDona) ?>;" role="img"
         aria-label="<?= e($total) ?> equipos: <?php foreach ($segmentos as [$texto, $cantidad]) { echo e("$texto $cantidad, "); } ?>">
        <div class="dona-centro">
            <strong><?= e($total) ?></strong>
            <span>Equipos totales</span>
        </div>
    </div>
    <ul class="leyenda">
        <?php foreach ($segmentos as [$texto, $cantidad, $color]): ?>
            <li class="leyenda-item">
                <span class="leyenda-color" style="background: var(<?= e($color) ?>);" aria-hidden="true"></span>
                <?= e($texto) ?>
                <span class="leyenda-valor">
                    <?= e($cantidad) ?> (<?= $total > 0 ? e(round($cantidad * 100 / $total)) : 0 ?> %)
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
