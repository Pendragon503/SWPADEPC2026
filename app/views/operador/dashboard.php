<?php
/**
 * Panel del Operador. Los indicadores, la dona y los avisos son datos reales;
 * los paneles con "estado vacío" se conectan en sus módulos.
 *
 * @var array $indicadores
 */

// [etiqueta, valor, icono, color, nota, color de la nota]
$tarjetas = [
    ['Solicitudes pendientes', $indicadores['pendientes'], 'bi-inbox-fill', 'naranja', 'por aprobar', 'naranja'],
    ['Alquileres activos', $indicadores['alquileresActivos'], 'bi-truck', 'azul', 'equipos en obra', 'azul'],
    ['Atrasados', $indicadores['atrasados'], 'bi-clock-history', 'rojo', 'sin devolver', 'rojo'],
    ['En mantenimiento', $indicadores['enMantenimiento'], 'bi-wrench-adjustable', 'verde', 'equipos en taller', 'verde'],
];

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

<div class="row g-3 mb-4">
    <?php foreach ($tarjetas as [$etiqueta, $valor, $icono, $color, $nota, $colorNota]): ?>
        <div class="col-6 col-xl-3">
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
    <div class="col-xl-7">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Actividad semanal (alquileres entregados)</h3>
            </div>
            <div class="panel-cuerpo">
                <div class="estado-vacio">
                    <span class="estado-vacio-icono"><i class="bi bi-bar-chart" aria-hidden="true"></i></span>
                    <p class="estado-vacio-titulo">Sin datos todavía</p>
                    <p class="estado-vacio-texto">Se conectará en el módulo de Reportes.</p>
                </div>
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
                <a href="#" class="btn btn-secondary btn-sm">Ver todas</a>
            </div>
            <div class="panel-cuerpo">
                <div class="estado-vacio">
                    <span class="estado-vacio-icono"><i class="bi bi-truck" aria-hidden="true"></i></span>
                    <p class="estado-vacio-titulo">Sin datos todavía</p>
                    <p class="estado-vacio-texto">Se conectará en el módulo de Entregas.</p>
                </div>
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
