<?php
/**
 * Layout de las páginas con sesión: barra superior, menú lateral por rol y mensajes.
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
<nav class="navbar navbar-expand-md navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#menuLateral"
                aria-controls="menuLateral" aria-expanded="false" aria-label="Mostrar menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand fw-semibold" href="<?= e(url('/')) ?>"><i class="bi bi-cone-striped"></i> EquipRent</a>
        <div class="d-flex align-items-center gap-3 ms-auto text-white">
            <span class="d-none d-sm-inline">
                <?= e(Auth::nombreCompleto()) ?>
                <span class="badge text-bg-secondary ms-1"><?= e(Auth::rol()) ?></span>
            </span>
            <form method="post" action="<?= e(url('/logout')) ?>" class="m-0">
                <?= csrfCampo() ?>
                <button type="submit" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Salir</button>
            </form>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <aside id="menuLateral" class="col-md-3 col-lg-2 collapse d-md-block bg-white border-end menu-lateral py-3">
            <?php require RAIZ . '/app/views/layouts/menu.php'; ?>
        </aside>

        <main class="col-md-9 col-lg-10 py-4 px-md-4">
            <?php foreach (flashes() as $mensaje): ?>
                <div class="alert alert-<?= e($mensaje['tipo']) ?> alert-dismissible fade show" role="alert">
                    <?= e($mensaje['mensaje']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endforeach; ?>

            <?= $contenido ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
