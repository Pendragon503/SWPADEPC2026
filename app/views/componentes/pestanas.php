<?php
/**
 * Barra de pestañas de una sección. Cada pestaña es un enlace (?tab=): no usa JavaScript.
 * Antes de incluirla, la vista define:
 *   $grupo    'alquileres' | 'operacion' | 'mantenimiento' | 'configuracion' (ver pestanas() en helpers)
 *   $pestana  la clave de la pestaña actual
 */
[$rutaPestanas, $listaPestanas] = pestanas($grupo);
?>
<nav class="pestanas" aria-label="Secciones">
    <?php foreach ($listaPestanas as $clave => [$texto, $icono]): ?>
        <a class="pestanas-enlace<?= $clave === $pestana ? ' activa' : '' ?>" href="<?= e(url($rutaPestanas . '?tab=' . $clave)) ?>"
            <?= $clave === $pestana ? 'aria-current="page"' : '' ?>>
            <i class="bi <?= e($icono) ?>" aria-hidden="true"></i> <?= e($texto) ?>
        </a>
    <?php endforeach; ?>
</nav>
