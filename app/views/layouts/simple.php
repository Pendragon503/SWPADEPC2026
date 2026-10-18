<?php
/**
 * Layout sin menú: login y páginas de error.
 *
 * @var string $titulo
 * @var string $contenido
 */
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'EquipRent') ?> · EquipRent</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="bg-body-tertiary">
<main class="container py-5">
    <div class="text-center mb-4">
        <a href="<?= e(url('/')) ?>" class="fs-3 fw-semibold text-decoration-none text-body"><i class="bi bi-cone-striped text-warning"></i> EquipRent</a>
    </div>

    <?php foreach (flashes() as $mensaje): ?>
        <div class="alert alert-<?= e($mensaje['tipo']) ?> mx-auto contenido-angosto" role="alert"><?= e($mensaje['mensaje']) ?></div>
    <?php endforeach; ?>

    <?= $contenido ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
