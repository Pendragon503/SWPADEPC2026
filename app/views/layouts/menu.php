<?php
/**
 * Menú lateral según el rol en sesión. Los módulos todavía no existen:
 * por ahora son enlaces de prueba (#).
 */
$menus = [
    'Administrador' => [
        ['bi-people', 'Usuarios y roles'],
        ['bi-box-seam', 'Inventario'],
        ['bi-tags', 'Categorías'],
        ['bi-cash-coin', 'Tarifas'],
        ['bi-person-vcard', 'Clientes'],
        ['bi-file-earmark-text', 'Contratos'],
        ['bi-gear', 'Configuración'],
        ['bi-bar-chart', 'Reportes'],
    ],
    'Operador' => [
        ['bi-inbox', 'Solicitudes'],
        ['bi-truck', 'Entregas'],
        ['bi-arrow-return-left', 'Devoluciones'],
        ['bi-exclamation-triangle', 'Daños'],
        ['bi-tools', 'Mantenimiento'],
    ],
    'Cliente' => [
        ['bi-grid', 'Catálogo'],
        ['bi-calendar-check', 'Disponibilidad'],
        ['bi-cart-plus', 'Solicitar alquiler'],
        ['bi-clock-history', 'Mis alquileres'],
        ['bi-person-circle', 'Perfil'],
    ],
];
?>
<nav class="nav nav-pills flex-column gap-1">
    <a class="nav-link active" href="<?= e(url(Auth::rutaInicio())) ?>"><i class="bi bi-speedometer2 me-2"></i>Panel</a>
    <?php foreach ($menus[Auth::rol()] ?? [] as [$icono, $nombre]): ?>
        <a class="nav-link link-body-emphasis" href="#"><i class="bi <?= e($icono) ?> me-2"></i><?= e($nombre) ?></a>
    <?php endforeach; ?>
</nav>
