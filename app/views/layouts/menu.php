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
        // Las tarifas se administran dentro del detalle de cada equipo (Inventario).
        ['bi-box-seam-fill', 'Inventario', '/admin/equipos'],
        ['bi-tags-fill', 'Categorías', '/admin/categorias'],
        ['bi-person-vcard-fill', 'Clientes', '/clientes'],
        ['bi-inbox-fill', 'Solicitudes', '/solicitudes'],
        ['bi-file-earmark-text-fill', 'Contratos', '/contratos'],
        ['bi-wrench-adjustable', 'Mantenimiento', '/mantenimiento'], // RF-12: también lo usa el administrador
        ['bi-gear-fill', 'Configuración', '#'],
        ['bi-bar-chart-fill', 'Reportes', '/admin/reportes'],
    ],
    'Operador' => [
        ['bi-speedometer2', 'Panel', '/operador'],
        ['bi-inbox-fill', 'Solicitudes', '/solicitudes'],
        ['bi-file-earmark-text-fill', 'Contratos', '/contratos'], // RF-08: consulta y registro de alquileres
        ['bi-person-vcard-fill', 'Clientes', '/clientes'], // RF-03: también lo usa el operador
        ['bi-truck', 'Entregas', '/entregas'],
        ['bi-arrow-left-right', 'Devoluciones', '/devoluciones'],
        ['bi-exclamation-triangle-fill', 'Daños', '/danos'],
        ['bi-wrench-adjustable', 'Mantenimiento', '/mantenimiento'],
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
