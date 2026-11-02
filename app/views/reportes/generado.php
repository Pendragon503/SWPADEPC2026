<?php
/**
 * Un reporte de "Ver reportes" en una página imprimible (vista previa o PDF). Con
 * $imprimir abre sola la ventana de impresión (data-imprimir-al-cargar de app.js).
 *
 * @var string $tipo, $desde, $hasta
 * @var array  $reporte   Reporte::generar(): ['titulo', 'secciones']
 * @var bool   $imprimir
 * @var string $urlExcel  el mismo reporte en CSV
 */
$detalleImpresion = $reporte['titulo'] . ' · ' . ($tipo === 'inventario' ? 'corte al ' . fechaCorta($desde) : 'del ' . periodo($desde, $hasta));
require RAIZ . '/app/views/reportes/_encabezadoImpresion.php';

// Valor de una celda según el formato de su columna.
$celda = function ($valor, string $formato): string {
    if ($valor === null || $valor === '') {
        return '<span class="texto-suave">—</span>';
    }
    switch ($formato) {
        case 'dinero': return dinero($valor);
        case 'fecha':  return e(fechaCorta((string) $valor, strlen((string) $valor) > 10));
        case 'estado': return estadoBadge((string) $valor);
        default:       return e($valor);
    }
};
?>
<a href="<?= e(url('/admin/configuracion?tab=reportes')) ?>" class="enlace-volver no-imprimir"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Ver reportes</a>

<div class="encabezado-pagina"<?= $imprimir ? ' data-imprimir-al-cargar' : '' ?>>
    <div>
        <h2 class="encabezado-pagina-titulo"><?= e($reporte['titulo']) ?></h2>
        <p class="encabezado-pagina-texto"><?= e($tipo === 'inventario' ? 'Corte al ' . fechaCorta($desde) : 'Período: ' . periodo($desde, $hasta)) ?></p>
    </div>
    <div class="acciones-pagina no-imprimir align-items-start">
        <?php require RAIZ . '/app/views/reportes/_descarga.php'; ?>
    </div>
</div>

<?php foreach ($reporte['secciones'] as $seccion): ?>
    <section class="panel mb-4">
        <div class="panel-cabecera panel-cabecera-linea"><h3 class="panel-titulo panel-titulo-mayus"><?= e($seccion['titulo']) ?></h3></div>
        <div class="panel-cuerpo">
            <?php if (!$seccion['filas']): ?>
                <p class="texto-suave mb-0">Sin registros en este período.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table tabla-er">
                        <thead>
                        <tr>
                            <?php foreach ($seccion['columnas'] as [$texto, $formato]): ?>
                                <th scope="col"<?= in_array($formato, ['dinero', 'numero'], true) ? ' class="text-end"' : '' ?>><?= e($texto) ?></th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($seccion['filas'] as $fila): ?>
                            <tr>
                                <?php foreach ($seccion['columnas'] as $i => [, $formato]): ?>
                                    <td<?= in_array($formato, ['dinero', 'numero'], true) ? ' class="text-end"' : '' ?>><?= $celda($fila[$i], $formato) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <?php if ($seccion['total'] !== null): ?>
                            <tfoot>
                            <tr class="tabla-importe-total">
                                <?php foreach ($seccion['columnas'] as $i => [, $formato]): ?>
                                    <td<?= in_array($formato, ['dinero', 'numero'], true) ? ' class="text-end"' : '' ?>><?= $i === 0 ? '<strong>Total</strong>' : ($seccion['total'][$i] === '' ? '' : ($formato === 'dinero' ? dinero($seccion['total'][$i]) : e($seccion['total'][$i]))) ?></td>
                                <?php endforeach; ?>
                            </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endforeach; ?>
