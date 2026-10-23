<?php
/**
 * Barra de progreso de un contrato (Figma "Detalle - alquiler - Daniel").
 * Antes de incluirla, la vista define $pasos = Contrato::pasosProgreso($contrato).
 */
?>
<ol class="progreso-contrato">
    <?php foreach ($pasos as $paso): ?>
        <li class="progreso-paso progreso-<?= e($paso['estado']) ?>"<?= $paso['estado'] === 'actual' ? ' aria-current="step"' : '' ?>>
            <span class="progreso-icono" aria-hidden="true">
                <i class="bi <?= e($paso['estado'] === 'hecho' ? 'bi-check-lg' : $paso['icono']) ?>"></i>
            </span>
            <span class="progreso-texto"><?= e($paso['texto']) ?></span>
        </li>
    <?php endforeach; ?>
</ol>
