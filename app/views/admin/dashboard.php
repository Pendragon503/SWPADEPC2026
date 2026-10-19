<?php
/**
 * Panel del Administrador. Los indicadores y el inventario son datos reales;
 * los paneles con "estado vacío" se conectan en sus módulos.
 *
 * @var array $indicadores
 * @var array $inventario
 */
$total = $indicadores['equiposTotales'];
$porcentaje = function (int $cantidad) use ($total): string {
    return $total > 0 ? round($cantidad * 100 / $total) . ' % del total' : '—';
};

// [etiqueta, valor, icono, color, nota, color de la nota]
$tarjetas = [
    ['Equipos totales', $total, 'bi-boxes', 'naranja', 'en inventario', 'gris'],
    ['Alquilados', $indicadores['alquilados'], 'bi-truck', 'naranja', $porcentaje($indicadores['alquilados']), 'naranja'],
    ['Disponibles', $indicadores['disponibles'], 'bi-check-circle-fill', 'verde', $porcentaje($indicadores['disponibles']), 'verde'],
    ['En mantenimiento', $indicadores['enMantenimiento'], 'bi-wrench-adjustable', 'azul', $porcentaje($indicadores['enMantenimiento']), 'azul'],
    ['Fuera de servicio', $indicadores['fueraServicio'], 'bi-x-circle-fill', 'rojo', $porcentaje($indicadores['fueraServicio']), 'rojo'],
    ['Atrasados', $indicadores['atrasados'], 'bi-clock-history', 'rojo', 'contratos sin devolver', 'rojo'],
];
?>
<div class="saludo">
    <h2 class="saludo-titulo">¡Hola, <?= e(Auth::usuario()['nombres']) ?>!</h2>
    <p class="saludo-texto">Resumen del estado de los equipos y los alquileres.</p>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($tarjetas as [$etiqueta, $valor, $icono, $color, $nota, $colorNota]): ?>
        <div class="col-6 col-md-4 col-xxl-2">
            <div class="indicador">
                <div class="indicador-cabecera">
                    <span class="indicador-etiqueta"><?= e($etiqueta) ?></span>
                    <span class="indicador-icono icono-<?= e($color) ?>"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i></span>
                </div>
                <div class="indicador-pie">
                    <span class="indicador-valor"><?= e($valor) ?></span>
                    <span class="indicador-nota nota-<?= e($colorNota) ?>"><?= e($nota) ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-5">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Resumen de alquileres</h3>
            </div>
            <div class="panel-cuerpo">
                <div class="estado-vacio">
                    <span class="estado-vacio-icono"><i class="bi bi-pie-chart" aria-hidden="true"></i></span>
                    <p class="estado-vacio-titulo">Sin datos todavía</p>
                    <p class="estado-vacio-texto">Se conectará en el módulo de Reportes.</p>
                </div>
            </div>
        </section>
    </div>
    <div class="col-xl-7">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Alquileres próximos a finalizar</h3>
            </div>
            <div class="panel-cuerpo">
                <div class="estado-vacio">
                    <span class="estado-vacio-icono"><i class="bi bi-calendar-event" aria-hidden="true"></i></span>
                    <p class="estado-vacio-titulo">Sin datos todavía</p>
                    <p class="estado-vacio-texto">Se conectará en el módulo de Contratos.</p>
                </div>
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
        <a href="#" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Registrar equipo
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
            Se muestran los primeros <?= e(count($inventario)) ?> de <?= e($total) ?> equipos. El listado completo llega con el módulo de Inventario.
        </p>
    </div>
</section>
