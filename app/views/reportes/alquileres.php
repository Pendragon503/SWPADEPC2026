<?php
/**
 * Reporte de alquileres (RF-14): por período, cliente, equipo o estado, con totales.
 * Apto para imprimir: lo marcado con .no-imprimir no sale en papel (ver @media print).
 *
 * @var array $filtros  ['desde', 'hasta', 'agrupar', 'estado']
 * @var array  $reporte   Reporte::resumen(): ['filas', 'total']
 * @var string $urlExcel  el mismo reporte en CSV
 */
[$tituloGrupo] = Reporte::AGRUPACIONES[$filtros['agrupar']];
$detalleImpresion = 'Alquileres por ' . mb_strtolower($tituloGrupo) . ' · del ' . periodo($filtros['desde'], $filtros['hasta'])
    . ' · ' . ($filtros['estado'] === '' ? 'sin Rechazados ni Cancelados' : 'solo ' . $filtros['estado']);
require RAIZ . '/app/views/reportes/_encabezadoImpresion.php';
?>
<a href="<?= e(url('/admin/configuracion?tab=reportes')) ?>" class="enlace-volver no-imprimir"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Ver reportes</a>

<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo">Reporte de alquileres por <?= e(mb_strtolower($tituloGrupo)) ?></h2>
        <p class="encabezado-pagina-texto">
            Contratos con inicio del <?= e(periodo($filtros['desde'], $filtros['hasta'])) ?> ·
            <?= $filtros['estado'] === '' ? 'sin Rechazados ni Cancelados' : 'solo ' . e($filtros['estado']) ?>
        </p>
    </div>
    <div class="acciones-pagina no-imprimir align-items-start">
        <a href="<?= e(url('/admin/reportes/equipo')) ?>" class="btn btn-secondary"><i class="bi bi-clock-history" aria-hidden="true"></i> Historial de un equipo</a>
        <?php require RAIZ . '/app/views/reportes/_descarga.php'; ?>
    </div>
</div>

<section class="panel mb-4 no-imprimir">
    <div class="panel-cuerpo">
        <form method="get" action="<?= e(url('/admin/reportes/alquileres')) ?>" class="filtros-contratos">
            <div>
                <label for="desde" class="form-label">Desde</label>
                <input type="date" id="desde" name="desde" class="form-control" value="<?= e($filtros['desde']) ?>">
            </div>
            <div>
                <label for="hasta" class="form-label">Hasta</label>
                <input type="date" id="hasta" name="hasta" class="form-control" value="<?= e($filtros['hasta']) ?>">
            </div>
            <div>
                <label for="agrupar" class="form-label">Agrupar por</label>
                <select id="agrupar" name="agrupar" class="form-select">
                    <?php foreach (Reporte::AGRUPACIONES as $clave => [$titulo]): ?>
                        <option value="<?= e($clave) ?>"<?= $clave === $filtros['agrupar'] ? ' selected' : '' ?>><?= e($titulo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos menos Rechazado y Cancelado</option>
                    <?php foreach (Contrato::ESTADOS as $estado): ?>
                        <option<?= $estado === $filtros['estado'] ? ' selected' : '' ?>><?= e($estado) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtros-tabla-botones filtros-contratos-botones">
                <a href="<?= e(url('/admin/reportes/alquileres')) ?>" class="btn btn-secondary">Mes actual</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-bar-chart" aria-hidden="true"></i> Generar</button>
            </div>
        </form>
    </div>
</section>

<section class="panel">
    <div class="panel-cuerpo">
        <?php if (!$reporte['filas']): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-bar-chart" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">No hay contratos en ese rango</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col"><?= e($tituloGrupo) ?></th>
                        <th scope="col" class="text-end">Contratos</th>
                        <th scope="col" class="text-end">Días-equipo</th>
                        <th scope="col" class="text-end">Importe</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reporte['filas'] as $fila): ?>
                        <tr>
                            <td>
                                <?php if ($filtros['agrupar'] === 'equipo'): ?>
                                    <a href="<?= e(url('/admin/reportes/equipo?id=' . $fila['clave'])) ?>"><?= e($fila['grupo']) ?></a>
                                <?php elseif ($filtros['agrupar'] === 'estado'): ?>
                                    <?= estadoBadge($fila['grupo']) ?>
                                <?php else: ?>
                                    <?= e($fila['grupo']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= e($fila['contratos']) ?></td>
                            <td class="text-end"><?= e($fila['dias']) ?></td>
                            <td class="text-end texto-fuerte"><?= dinero($fila['importe']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="tabla-importe-total">
                        <th scope="row">Total</th>
                        <td class="text-end"><?= e($reporte['total']['contratos']) ?></td>
                        <td class="text-end"><?= e($reporte['total']['dias']) ?></td>
                        <td class="text-end"><?= dinero($reporte['total']['importe']) ?></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <p class="form-text mb-0">Días-equipo: días facturados de cada equipo sumados. Importe: tarifas por días, sin depósito de garantía.
                Un contrato con varios equipos cuenta una vez en el total.</p>
        <?php endif; ?>
    </div>
</section>
