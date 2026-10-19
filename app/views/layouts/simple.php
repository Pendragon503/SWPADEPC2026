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
<?php require RAIZ . '/app/views/layouts/recursos.php'; ?>
</head>
<body class="app-er pantalla-acceso">
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

<?php require RAIZ . '/app/views/layouts/scripts.php'; ?>
</body>
</html>
