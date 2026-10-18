<?php
/**
 * @var Throwable|null $excepcion  Solo viene con debug activado.
 */
?>
<div class="mx-auto <?= $excepcion ? '' : 'contenido-angosto text-center' ?>">
    <?php if ($excepcion === null): ?>
        <p class="display-1 fw-bold text-danger mb-0">500</p>
        <h1 class="h4">Ocurrió un error</h1>
        <p class="text-secondary">No pudimos completar tu solicitud. Inténtalo de nuevo en unos minutos.</p>
        <a href="<?= e(url('/')) ?>" class="btn btn-dark">Volver al inicio</a>
    <?php else: ?>
        <div class="alert alert-danger">
            <h1 class="h5 mb-2">Ocurrió un error <span class="badge text-bg-danger">debug</span></h1>
            <p class="mb-1"><strong><?= e(get_class($excepcion)) ?>:</strong> <?= e($excepcion->getMessage()) ?></p>
            <p class="mb-0 small font-monospace"><?= e($excepcion->getFile()) ?>:<?= e($excepcion->getLine()) ?></p>
        </div>
        <h2 class="h6">Traza</h2>
        <ol class="small font-monospace bg-white border rounded p-3 ps-5">
            <?php foreach ($excepcion->getTrace() as $paso): ?>
                <li>
                    <?= e(($paso['class'] ?? '') . ($paso['type'] ?? '') . ($paso['function'] ?? '')) ?>()
                    <span class="text-secondary"><?= e(($paso['file'] ?? '[interno]') . (isset($paso['line']) ? ':' . $paso['line'] : '')) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
        <p class="small text-secondary">Este detalle solo se muestra porque <code>app.debug</code> está activado en config.local.php.</p>
    <?php endif; ?>
</div>
