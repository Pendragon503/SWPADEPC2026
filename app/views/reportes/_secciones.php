<?php
/**
 * Tablas de un reporte de "Ver reportes", con su total. Las usan la pantalla
 * (reportes/generado.php) y el archivo PDF (reportes/pdf.php, con sus propios estilos).
 *
 * Antes de incluirlo, la vista define:
 *   $reporte   Reporte::generar(): ['titulo', 'secciones']
 */

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
