<?php
/**
 * Entregas pendientes y devoluciones pendientes (una sola vista para las dos listas).
 * Entregas: contratos Aprobados, o Activos con entrega parcial, de la fecha de inicio
 * más antigua. Devoluciones: contratos Activos con equipos por recibir, del más
 * atrasado al más reciente; los atrasados se marcan en rojo.
 *
 * @var string $tipo       'entrega' | 'devolucion'
 * @var array  $contratos  Contrato::buscar()
 * @var int    $total, $pagina, $totalPaginas, $desplazamiento
 */
$esEntrega = $tipo === 'entrega';
?>
<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo"><?= $esEntrega ? 'Entregas pendientes' : 'Devoluciones pendientes' ?></h2>
        <p class="encabezado-pagina-texto">
            <?= $esEntrega
                ? 'Contratos aprobados con equipos por entregar en obra, del que debía salir primero.'
                : 'Contratos activos con equipos por recibir, del más atrasado al más reciente.' ?>
        </p>
    </div>
</div>

<section class="panel">
    <div class="panel-cuerpo">
        <?php if (!$contratos): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi <?= $esEntrega ? 'bi-truck' : 'bi-arrow-repeat' ?>" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo"><?= $esEntrega ? 'No hay entregas pendientes' : 'No hay devoluciones pendientes' ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col">Número</th>
                        <th scope="col">Cliente / proyecto</th>
                        <th scope="col" class="d-none d-md-table-cell">Equipos</th>
                        <th scope="col" class="d-none d-lg-table-cell">Período</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="col-acciones">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($contratos as $contrato): ?>
                        <?php $atraso = $esEntrega ? 0 : diasAtraso($contrato['fechaFin']); ?>
                        <tr>
                            <td><span class="codigo"><?= e($contrato['numeroContrato']) ?></span></td>
                            <td>
                                <span class="texto-fuerte"><?= e($contrato['cliente']) ?></span>
                                <br><span class="texto-suave small"><?= e($contrato['destino'] ?? $contrato['codigoCliente']) ?></span>
                            </td>
                            <td class="d-none d-md-table-cell"><?= resumenEquipos($contrato['primerEquipo'], (int) $contrato['equipos']) ?></td>
                            <td class="d-none d-lg-table-cell text-nowrap"><?= e(periodo($contrato['fechaInicio'], $contrato['fechaFin'])) ?></td>
                            <td>
                                <?= estadoBadge($contrato['estado']) ?>
                                <?php if ($atraso > 0): ?>
                                    <span class="estado estado-rojo"><?= e($atraso) ?> <?= $atraso === 1 ? 'día' : 'días' ?> de atraso</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-acciones text-nowrap">
                                <a href="<?= e(url('/contratos/detalle?id=' . $contrato['idContrato'])) ?>" class="btn btn-claro btn-sm"
                                   title="Ver detalle" aria-label="Ver <?= e($contrato['numeroContrato']) ?>"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                <a href="<?= e(url(($esEntrega ? '/entregas' : '/devoluciones') . '/registrar?id=' . $contrato['idContrato'])) ?>" class="btn btn-primary btn-sm">
                                    <?= $esEntrega ? 'Registrar entrega' : 'Registrar devolución' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pie-tabla">
                <p class="pie-tabla-texto">Mostrando <?= e($desplazamiento + 1) ?>–<?= e($desplazamiento + count($contratos)) ?> de <?= e($total) ?></p>
                <?php
                $rutaPaginacion = $esEntrega ? '/entregas' : '/devoluciones';
                $parametrosPaginacion = [];
                require RAIZ . '/app/views/componentes/paginacion.php';
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>
