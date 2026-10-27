<?php
/**
 * Menú lateral de Administrador y Operador (Figma). El menú del Cliente está en layouts/cliente.php.
 *
 * Cada fila es [icono, texto, ruta]. Varias secciones juntan páginas en pestañas (?tab=,
 * componentes/pestanas.php); sus URL anteriores (/solicitudes, /devoluciones, /danos,
 * /admin/categorias, /admin/usuarios, /admin/reportes) siguen funcionando.
 * "Mi perfil" está en la píldora de la barra superior.
 * $menuActivo (opcional) marca el enlace actual; por defecto, el Dashboard.
 * Iconos: https://icons.getbootstrap.com
 */
$menus = [
    'Administrador' => [
        ['bi-speedometer2', 'Dashboard', '/admin'],
        ['bi-box-seam-fill', 'Equipos', '/admin/equipos'],
        ['bi-tags-fill', 'Categorías', '/admin/configuracion?tab=categorias'],
        ['bi-file-earmark-text-fill', 'Alquileres', '/contratos'],
        ['bi-person-vcard-fill', 'Clientes', '/clientes'],
        ['bi-truck', 'Entregas y Devoluciones', '/entregas'],
        ['bi-wrench-adjustable', 'Mantenimiento', '/mantenimiento'],
        ['bi-gear-fill', 'Configuración', '/admin/configuracion'],
    ],
    'Operador' => [
        ['bi-speedometer2', 'Dashboard', '/operador'],
        ['bi-box-seam-fill', 'Equipos', '/admin/equipos'], // solo consulta
        ['bi-file-earmark-text-fill', 'Alquileres', '/contratos'],
        ['bi-person-vcard-fill', 'Clientes', '/clientes'],
        ['bi-truck', 'Entregas y Devoluciones', '/entregas'],
        ['bi-wrench-adjustable', 'Mantenimiento', '/mantenimiento'],
    ],
];

$activo = $menuActivo ?? 'Dashboard';
?>
<nav class="menu-lateral" aria-label="Menú principal">
    <?php foreach ($menus[Auth::rol()] ?? [] as [$icono, $texto, $ruta]): ?>
        <?php $esActivo = ($texto === $activo); ?>
        <a class="menu-lateral-enlace<?= $esActivo ? ' activo' : '' ?>"
           href="<?= e(url($ruta)) ?>"<?= $esActivo ? ' aria-current="page"' : '' ?>>
            <i class="bi <?= e($icono) ?>" aria-hidden="true"></i><?= e($texto) ?>
        </a>
    <?php endforeach; ?>
</nav>
