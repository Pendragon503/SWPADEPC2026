<?php
/**
 * Layout del Cliente: barra superior oscura con el menú, "Mi solicitud" y el usuario.
 * En pantallas pequeñas el menú se pliega detrás del botón de hamburguesa.
 *
 * @var string $titulo
 * @var string $contenido
 */
$usuario = Auth::usuario();

// [clave, icono, texto, ruta]. Los módulos que todavía no existen llevan '#'.
$menuCliente = [
    ['inicio', 'bi-house-door-fill', 'Inicio', '/cliente'],
    ['catalogo', 'bi-grid-3x3-gap-fill', 'Catálogo', '/cliente/catalogo'],
    ['disponibilidad', 'bi-calendar-check-fill', 'Disponibilidad', '#'],
    ['alquileres', 'bi-clipboard-fill', 'Mis alquileres', '#'],
    ['perfil', 'bi-person-fill', 'Mi perfil', '#'],
];
$activo = $menuActivo ?? 'inicio';

// Equipos agregados a la solicitud en curso (RF-06). Se llena en el módulo de Catálogo.
$equiposEnSolicitud = 0;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'EquipRent') ?> · EquipRent</title>
<?php require RAIZ . '/app/views/layouts/recursos.php'; ?>
</head>
<body class="app-er app-cliente">
<nav class="navbar navbar-expand-lg barra-cliente" data-bs-theme="dark" aria-label="Menú principal">
    <div class="contenedor-cliente barra-cliente-contenido">
        <a class="marca-cliente" href="<?= e(url('/cliente')) ?>">
            <span class="marca-icono marca-icono-40" aria-hidden="true"><i class="bi bi-cone-striped"></i></span>
            <span>
                <span class="marca-cliente-nombre">EQUIP<span>RENT</span></span>
                <span class="marca-cliente-subtitulo">Alquiler de equipos</span>
            </span>
        </a>

        <div class="acciones-cliente order-lg-3">
            <a class="boton-solicitud" href="#" aria-label="Mi solicitud: <?= e($equiposEnSolicitud) ?> equipos">
                <span class="boton-solicitud-icono">
                    <i class="bi bi-cart3" aria-hidden="true"></i>
                    <span class="contador-solicitud"><?= e($equiposEnSolicitud) ?></span>
                </span>
                <span class="boton-solicitud-texto">Mi solicitud</span>
            </a>
            <span class="pildora-usuario pildora-usuario-oscura">
                <span class="avatar-iniciales" aria-hidden="true"><?= e(iniciales($usuario['nombres'], $usuario['apellidos'])) ?></span>
                <span class="pildora-usuario-texto">
                    <strong><?= e(Auth::nombreCompleto()) ?></strong>
                    <small>Cliente</small>
                </span>
            </span>
            <form method="post" action="<?= e(url('/logout')) ?>" class="m-0">
                <?= csrfCampo() ?>
                <button type="submit" class="boton-salir boton-salir-oscuro" aria-label="Cerrar sesión">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Salir</span>
                </button>
            </form>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuCliente"
                aria-controls="menuCliente" aria-expanded="false" aria-label="Mostrar menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse order-lg-2" id="menuCliente">
            <ul class="navbar-nav menu-cliente mx-lg-auto">
                <?php foreach ($menuCliente as [$clave, $icono, $texto, $ruta]): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $clave === $activo ? ' active' : '' ?>"
                           href="<?= $ruta === '#' ? '#' : e(url($ruta)) ?>"<?= $clave === $activo ? ' aria-current="page"' : '' ?>>
                            <i class="bi <?= e($icono) ?>" aria-hidden="true"></i><?= e($texto) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>

<main>
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="contenedor-cliente pt-3">
            <?php require RAIZ . '/app/views/layouts/mensajes.php'; ?>
        </div>
    <?php endif; ?>
    <?= $contenido ?>
</main>

<footer class="pie-cliente">
    © 2026 EquipRent · Sistema de Alquiler de Equipos para Construcción. Módulo de Cliente.
</footer>
<?php require RAIZ . '/app/views/layouts/scripts.php'; ?>
</body>
</html>
