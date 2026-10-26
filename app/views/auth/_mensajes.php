<?php
/**
 * Mensajes flash dentro de la tarjeta del login y del registro (por eso el layout
 * simple no los repite).
 */
$iconosAlerta = [
    'danger'  => 'bi-exclamation-circle-fill',
    'warning' => 'bi-exclamation-triangle-fill',
    'success' => 'bi-check-circle-fill',
    'info'    => 'bi-info-circle-fill',
];
?>
<?php foreach (flashes() as $mensaje): ?>
    <div class="alert alert-<?= e($mensaje['tipo']) ?> alerta-acceso" role="alert">
        <i class="bi <?= e($iconosAlerta[$mensaje['tipo']] ?? 'bi-info-circle-fill') ?>" aria-hidden="true"></i>
        <span><?= e($mensaje['mensaje']) ?></span>
    </div>
<?php endforeach; ?>
