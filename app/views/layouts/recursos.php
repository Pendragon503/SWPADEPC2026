<?php
/**
 * Hojas de estilo comunes a todos los layouts (dentro de <head>).
 * Todo se sirve desde public/assets/vendor, sin depender de un CDN.
 * Si se actualiza una versión, cambiar la carpeta aquí y en scripts.php.
 */
?>
    <link rel="stylesheet" href="<?= e(asset('vendor/inter-5.2.8/inter.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-5.3.3/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons-1.11.3/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetConVersion('css/app.css')) ?>">
