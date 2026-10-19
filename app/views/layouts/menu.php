<?php
/**
 * Menú lateral de Administrador y Operador. El menú del Cliente está en layouts/cliente.php.
 *
 * Para agregar un módulo: añadir una fila [icono, texto, ruta] en el rol que corresponda.
 * Los módulos que todavía no existen llevan la ruta '#' (enlace de prueba).
 * $menuActivo (opcional) marca el enlace actual; por defecto, el Panel.
 * Iconos: https://icons.getbootstrap.com
 */
$menus = [
    'Administrador' => [
        ['bi-speedometer2', 'Panel', '/admin'],
        ['bi-people-fill', 'Usuarios y roles', '#'],
        ['bi-box-seam-fill', 'Inventario', '#'],
        ['bi-grid-fill', 'Categorías', '#'],
        ['bi-cash-coin', 'Tarifas', '#'],
        ['bi-person-vcard-fill', 'Clientes', '#'],
        ['bi-file-earmark-text-fill', 'Contratos', '#'],
        ['bi-gear-fill', 'Configuración', '#'],
        ['bi-bar-chart-fill', 'Reportes', '#'],
    ],
    'Operador' => [
        ['bi-speedometer2', 'Panel', '/operador'],
        ['bi-inbox-fill', 'Solicitudes', '#'],
        ['bi-truck', 'Entregas', '#'],
        ['bi-arrow-left-right', 'Devoluciones', '#'],
        ['bi-exclamation-triangle-fill', 'Daños', '#'],
        ['bi-wrench-adjustable', 'Mantenimiento', '#'],
    ],
];

$activo = $menuActivo ?? 'Panel';
?>
<nav class="menu-lateral" aria-label="Menú principal">
    <?php foreach ($menus[Auth::rol()] ?? [] as [$icono, $texto, $ruta]): ?>
        <?php $esActivo = ($texto === $activo); ?>
        <a class="menu-lateral-enlace<?= $esActivo ? ' activo' : '' ?>"
           href="<?= $ruta === '#' ? '#' : e(url($ruta)) ?>"<?= $esActivo ? ' aria-current="page"' : '' ?>>
            <i class="bi <?= e($icono) ?>" aria-hidden="true"></i><?= e($texto) ?>
        </a>
    <?php endforeach; ?>
</nav>
