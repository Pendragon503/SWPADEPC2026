<?php
/**
 * Mensajes flash (se muestran una sola vez). Tipos: success, danger, warning, info.
 */
$iconosMensaje = [
    'success' => 'bi-check-circle-fill',
    'danger'  => 'bi-exclamation-circle-fill',
    'warning' => 'bi-exclamation-triangle-fill',
    'info'    => 'bi-info-circle-fill',
];
?>
<?php foreach (flashes() as $mensaje): ?>
    <div class="alert alert-<?= e($mensaje['tipo']) ?> alert-dismissible fade show" role="alert">
        <i class="bi <?= e($iconosMensaje[$mensaje['tipo']] ?? 'bi-info-circle-fill') ?>" aria-hidden="true"></i>
        <span><?= e($mensaje['mensaje']) ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endforeach; ?>
