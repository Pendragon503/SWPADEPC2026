<?php
/**
 * Panel del Operador (RF-13): indicadores, entregas de la semana, estado de la
 * flota, próximas entregas y avisos. Todo con datos reales.
 *
 * @var array $indicadores  Indicador::resumenGeneral()
 * @var array $entregasDia  Indicador::entregasPorDia(): ['AAAA-MM-DD' => equipos]
 * @var array $proximas     contratos con equipos por entregar, del que debía salir primero
 */

// [etiqueta, valor, icono, color, nota, color de la nota, ruta]
$tarjetas = [
    ['Solicitudes', $indicadores['pendientes'], 'bi-inbox-fill', 'naranja', 'por aprobar', 'naranja', '/solicitudes'],
    ['Entregas', $indicadores['entregasPendientes'], 'bi-truck', 'azul', 'contratos por entregar', 'azul', '/entregas'],
    ['Devoluciones', $indicadores['devolucionesPendientes'], 'bi-arrow-left-right', 'verde', 'contratos por recibir', 'verde', '/devoluciones'],
    ['Atrasados', $indicadores['atrasados'], 'bi-clock-history', 'rojo', 'sin devolver', 'rojo', '/devoluciones'],
    ['Por finalizar', $indicadores['proximosAFinalizar'], 'bi-calendar-event', 'naranja', 'en los próximos 3 días', 'naranja', '/devoluciones'],
    ['Mantenimiento', $indicadores['mantenimientosAbiertos'], 'bi-wrench-adjustable', 'azul', 'órdenes abiertas', 'azul', '/mantenimiento?estado=abiertas'],
];
$columnas = 'col-6 col-md-4 col-xxl-2';
$maximoDia = max(1, ...array_values($entregasDia));

// Avisos que ya se pueden calcular con los indicadores.
$avisos = [];
$atrasados = $indicadores['atrasados'];
if ($atrasados > 0) {
    $texto = $atrasados === 1
        ? '1 contrato activo vencido sin devolución. El equipo sigue bloqueado hasta que regrese.'
        : "$atrasados contratos activos vencidos sin devolución. Los equipos siguen bloqueados hasta que regresen.";
    $avisos[] = ['rojo', 'bi-clock-history', 'Alquileres atrasados', $texto];
}
$pendientes = $indicadores['pendientes'];
if ($pendientes > 0) {
    $texto = $pendientes === 1
        ? '1 solicitud de alquiler esperando aprobación.'
        : "$pendientes solicitudes de alquiler esperando aprobación.";
    $avisos[] = ['naranja', 'bi-inbox-fill', 'Solicitudes por revisar', $texto];
}
$enMantenimiento = $indicadores['enMantenimiento'];
if ($enMantenimiento > 0) {
    $texto = $enMantenimiento === 1
        ? '1 equipo en taller; no se puede alquilar hasta que salga de mantenimiento.'
        : "$enMantenimiento equipos en taller; no se pueden alquilar hasta que salgan de mantenimiento.";
    $avisos[] = ['azul', 'bi-wrench-adjustable', 'Equipos en mantenimiento', $texto];
}
?>
<div class="saludo">
    <h2 class="saludo-titulo">¡Hola, <?= e(Auth::usuario()['nombres']) ?>!</h2>
    <p class="saludo-texto">Resumen de la actividad de maquinaria, entregas y devoluciones.</p>
</div>

<?php require RAIZ . '/app/views/componentes/indicadores.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-xl-7">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Actividad semanal (equipos entregados)</h3>
            </div>
            <div class="panel-cuerpo">
                <ol class="actividad-semanal">
                    <?php foreach ($entregasDia as $dia => $equipos): ?>
                        <li>
                            <span class="actividad-valor"><?= e($equipos) ?></span>
                            <span class="actividad-barra" style="height: <?= round($equipos * 100 / $maximoDia) ?>%"></span>
                            <span class="actividad-dia"><?= e(date('d/m', strtotime($dia))) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>
    </div>
    <div class="col-xl-5">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Estado de equipos en flota</h3>
            </div>
            <div class="panel-cuerpo">
                <?php require RAIZ . '/app/views/componentes/estadoEquipos.php'; ?>
            </div>
        </section>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Próximas entregas programadas</h3>
                <a href="<?= e(url('/entregas')) ?>" class="btn btn-secondary btn-sm">Ver todas</a>
            </div>
            <div class="panel-cuerpo">
                <?php $contratos = $proximas; $tipo = 'entrega'; require RAIZ . '/app/views/componentes/contratosResumen.php'; ?>
            </div>
        </section>
    </div>
    <div class="col-xl-5">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Alertas y avisos de maquinaria</h3>
            </div>
            <div class="panel-cuerpo">
                <?php if ($avisos): ?>
                    <ul class="avisos">
                        <?php foreach ($avisos as [$color, $icono, $tituloAviso, $texto]): ?>
                            <li class="aviso aviso-<?= e($color) ?>">
                                <i class="bi <?= e($icono) ?> aviso-icono" aria-hidden="true"></i>
                                <div>
                                    <p class="aviso-titulo"><?= e($tituloAviso) ?></p>
                                    <p class="aviso-texto"><?= e($texto) ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="estado-vacio">
                        <span class="estado-vacio-icono"><i class="bi bi-bell" aria-hidden="true"></i></span>
                        <p class="estado-vacio-titulo">Sin avisos</p>
                        <p class="estado-vacio-texto">No hay alquileres atrasados ni solicitudes pendientes.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
