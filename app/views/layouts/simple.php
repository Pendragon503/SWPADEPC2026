<?php
/**
 * Layout sin menú (login y páginas de error), con el diseño de Figma:
 * barra superior con la marca y foto de fondo al 25 %.
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400..800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="pantalla-acceso">
<header class="barra-acceso">
    <a class="marca" href="<?= e(url('/')) ?>">
        <span class="marca-icono" aria-hidden="true"><i class="bi bi-cone-striped"></i></span>
        <span class="marca-texto">EQUIP<span>RENT</span></span>
    </a>
    <span class="marca-separador" aria-hidden="true"></span>
    <span class="marca-subtitulo">Sistema de Alquiler de Equipos</span>
    <span class="barra-lema">Tu proyecto, nuestro equipo <span class="barra-lema-raya" aria-hidden="true"></span></span>
</header>

<main class="contenedor-acceso">
    <div class="w-100">
        <?php foreach (flashes() as $mensaje): ?>
            <div class="alert alert-<?= e($mensaje['tipo']) ?> mx-auto contenido-angosto" role="alert"><?= e($mensaje['mensaje']) ?></div>
        <?php endforeach; ?>

        <?= $contenido ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
