<?php
/**
 * Panel del Administrador (RF-13): indicadores, resumen de alquileres por estado,
 * próximos a finalizar, estado de equipos e inventario. Todo con datos reales.
 *
 * @var array $indicadores  Indicador::resumenGeneral()
 * @var array $inventario
 * @var array $proximos     contratos con equipos por recibir, del más atrasado
 */
$total = $indicadores['equiposTotales'];
$porcentaje = function (int $cantidad) use ($total): string {
    return $total > 0 ? round($cantidad * 100 / $total) . ' % del total' : '—';
};

// [etiqueta, valor, icono, color, nota, color de la nota, ruta]
$tarjetas = [
    ['Equipos totales', $total, 'bi-boxes', 'naranja', 'en inventario', 'gris', '/admin/equipos'],
    ['Alquilados', $indicadores['alquilados'], 'bi-truck', 'naranja', $porcentaje($indicadores['alquilados']), 'naranja', '/admin/equipos?estado=Alquilado'],
    ['Disponibles', $indicadores['disponibles'], 'bi-check-circle-fill', 'verde', $porcentaje($indicadores['disponibles']), 'verde', '/admin/equipos?estado=Disponible'],
    ['En mantenimiento', $indicadores['enMantenimiento'], 'bi-wrench-adjustable', 'azul', $indicadores['mantenimientosAbiertos'] . ' órdenes abiertas', 'azul', '/mantenimiento?estado=abiertas'],
    ['En revisión', $indicadores['enRevision'], 'bi-exclamation-triangle-fill', 'naranja', 'por daños', 'naranja', '/danos?estado=Reportado'],
    ['Atrasados', $indicadores['atrasados'], 'bi-clock-history', 'rojo', 'contratos sin devolver', 'rojo', '/devoluciones'],
];
$columnas = 'col-6 col-md-4 col-xxl-2';

// Contratos por estado para "Resumen de alquileres": [estado, cantidad]
$porEstado = [
    ['Pendiente', $indicadores['pendientes']], ['Aprobado', $indicadores['aprobados']],
    ['Activo', $indicadores['alquileresActivos']], ['Finalizado', $indicadores['finalizados']],
    ['Rechazado', $indicadores['rechazados']], ['Cancelado', $indicadores['cancelados']],
];
$maximo = max(1, ...array_column($porEstado, 1));
?>
<div class="saludo">
    <h2 class="saludo-titulo">¡Hola, <?= e(Auth::usuario()['nombres']) ?>!</h2>
    <p class="saludo-texto">Resumen del estado de los equipos y los alquileres.</p>
</div>

<?php require RAIZ . '/app/views/componentes/indicadores.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-xl-5">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Resumen de alquileres</h3>
                <a href="<?= e(url('/admin/reportes')) ?>" class="btn btn-secondary btn-sm">Reportes</a>
            </div>
            <div class="panel-cuerpo">
                <ul class="barras-estado">
                    <?php foreach ($porEstado as [$estado, $cantidad]): ?>
                        <li>
                            <a href="<?= e(url('/contratos?estado=' . $estado)) ?>"><?= estadoBadge($estado) ?></a>
                            <span class="barras-estado-barra"><span style="width: <?= round($cantidad * 100 / $maximo) ?>%"></span></span>
                            <span class="texto-fuerte"><?= e($cantidad) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    </div>
    <div class="col-xl-7">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Atrasados y próximos a finalizar</h3>
                <a href="<?= e(url('/devoluciones')) ?>" class="btn btn-secondary btn-sm">Ver todas</a>
            </div>
            <div class="panel-cuerpo">
                <?php $contratos = $proximos; $tipo = 'devolucion'; require RAIZ . '/app/views/componentes/contratosResumen.php'; ?>
            </div>
        </section>
    </div>
</div>

<section class="panel mb-4">
    <div class="panel-cabecera">
        <h3 class="panel-titulo">Estado de equipos</h3>
    </div>
    <div class="panel-cuerpo">
        <?php require RAIZ . '/app/views/componentes/estadoEquipos.php'; ?>
    </div>
</section>

<section class="panel">
    <div class="panel-cabecera">
        <h3 class="panel-titulo">Inventario de equipos</h3>
        <a href="<?= e(url('/admin/equipos')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-box-seam-fill" aria-hidden="true"></i> Ir al inventario
        </a>
    </div>
    <div class="panel-cuerpo">
        <div class="table-responsive">
            <table class="table tabla-er">
                <thead>
                <tr>
                    <th scope="col">Código</th>
                    <th scope="col">Nombre del equipo</th>
                    <th scope="col">Categoría</th>
                    <th scope="col" class="text-end">Precio diario</th>
                    <th scope="col">Estado</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($inventario as $equipo): ?>
                    <tr>
                        <td><span class="codigo"><?= e($equipo['codigoInventario']) ?></span></td>
                        <td class="texto-fuerte"><?= e($equipo['nombre']) ?></td>
                        <td><?= e($equipo['categoria']) ?></td>
                        <td class="text-end">$<?= e(number_format((float) $equipo['precioDiario'], 2)) ?></td>
                        <td><?= estadoBadge($equipo['estado']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="texto-suave small mt-3 mb-0">
            Se muestran los primeros <?= e(count($inventario)) ?> de <?= e($total) ?> equipos. <a href="<?= e(url('/admin/equipos')) ?>">Ver el inventario completo</a>.
        </p>
    </div>
</section>
