<?php
/**
 * Tabla corta de contratos para los paneles (próximas entregas o devoluciones).
 * Antes de incluirla, la vista define:
 *   $contratos  Contrato::buscar(['pendiente' => ...])
 *   $tipo       'entrega' (muestra el inicio) | 'devolucion' (muestra el fin y el atraso)
 */
?>
<?php if (!$contratos): ?>
    <div class="estado-vacio">
        <span class="estado-vacio-icono"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
        <p class="estado-vacio-titulo"><?= $tipo === 'entrega' ? 'No hay entregas pendientes' : 'No hay devoluciones pendientes' ?></p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table tabla-er">
            <thead><tr><th scope="col">Contrato</th><th scope="col">Cliente</th><th scope="col"><?= $tipo === 'entrega' ? 'Inicio' : 'Fin' ?></th><th scope="col">Estado</th></tr></thead>
            <tbody>
            <?php foreach ($contratos as $contrato): ?>
                <?php $atraso = $tipo === 'entrega' ? 0 : diasAtraso($contrato['fechaFin']); ?>
                <tr>
                    <td><a class="codigo" href="<?= e(url('/contratos/detalle?id=' . $contrato['idContrato'])) ?>"><?= e($contrato['numeroContrato']) ?></a></td>
                    <td><?= e($contrato['cliente']) ?><br><span class="texto-suave small"><?= resumenEquipos($contrato['primerEquipo'], (int) $contrato['equipos']) ?></span></td>
                    <td class="text-nowrap"><?= e(fechaCorta($tipo === 'entrega' ? $contrato['fechaInicio'] : $contrato['fechaFin'])) ?></td>
                    <td><?= $atraso > 0 ? '<span class="estado estado-rojo">' . e($atraso) . ' d de atraso</span>' : estadoBadge($contrato['estado']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
