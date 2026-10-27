<?php
/**
 * Solicitudes pendientes (RF-06), para Administrador y Operador: aprobar o rechazar.
 * Sin frame propio en Figma: tabla de "alquileres-main" con el estilo de Inventario.
 *
 * @var array      $solicitudes        Contrato::buscar() de las Pendientes, de la más antigua a la más reciente
 * @var array|null $formularioRechazo  modal de rechazo con errores (o null)
 * @var int        $total, $pagina, $totalPaginas, $desplazamiento
 */
?>
<?php
$pestana = 'solicitudes';
$textoPestana = 'Solicitudes de los clientes por aprobar, de la más antigua a la más reciente. Al aprobar se revisa otra vez que cada equipo siga libre.';
require RAIZ . '/app/views/contratos/_encabezado.php';
?>

<section class="panel">
    <div class="panel-cuerpo">
        <?php if (!$solicitudes): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-inbox" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">No hay solicitudes pendientes</p>
                <p class="estado-vacio-texto">Cuando un cliente envíe una solicitud desde el catálogo aparecerá aquí.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col">Número</th>
                        <th scope="col">Cliente</th>
                        <th scope="col" class="d-none d-md-table-cell">Equipos</th>
                        <th scope="col" class="d-none d-lg-table-cell">Período</th>
                        <th scope="col" class="text-end">Total</th>
                        <th scope="col" class="d-none d-xl-table-cell">Solicitada</th>
                        <th scope="col" class="col-acciones">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <tr>
                            <td><span class="codigo"><?= e($solicitud['numeroContrato']) ?></span></td>
                            <td>
                                <span class="texto-fuerte"><?= e($solicitud['cliente']) ?></span>
                                <br><span class="texto-suave small"><?= e($solicitud['codigoCliente']) ?></span>
                            </td>
                            <td class="d-none d-md-table-cell"><?= resumenEquipos($solicitud['primerEquipo'], (int) $solicitud['equipos']) ?></td>
                            <td class="d-none d-lg-table-cell text-nowrap">
                                <?= e(periodo($solicitud['fechaInicio'], $solicitud['fechaFin'])) ?>
                                <br><span class="texto-suave small"><?= e($solicitud['dias']) ?> <?= (int) $solicitud['dias'] === 1 ? 'día' : 'días' ?></span>
                            </td>
                            <td class="text-end texto-fuerte"><?= dinero($solicitud['totalEstimado']) ?></td>
                            <td class="d-none d-xl-table-cell texto-suave text-nowrap"><?= e(fechaCorta($solicitud['fechaSolicitud'], true)) ?></td>
                            <td class="col-acciones text-nowrap">
                                <a href="<?= e(url('/contratos/detalle?id=' . $solicitud['idContrato'])) ?>" class="btn btn-claro btn-sm"
                                   title="Ver detalle" aria-label="Ver <?= e($solicitud['numeroContrato']) ?>">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                                <form method="post" action="<?= e(url('/contratos/aprobar')) ?>" class="d-inline"
                                      data-confirmar="¿Aprobar <?= e($solicitud['numeroContrato']) ?> de <?= e($solicitud['cliente']) ?>?"
                                      data-enviando="">
                                    <?= csrfCampo() ?>
                                    <input type="hidden" name="idContrato" value="<?= e($solicitud['idContrato']) ?>">
                                    <input type="hidden" name="volver" value="solicitudes">
                                    <button type="submit" class="btn btn-primary btn-sm" title="Aprobar" aria-label="Aprobar <?= e($solicitud['numeroContrato']) ?>">
                                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#modalRechazo"
                                        data-accion="<?= e(url('/contratos/rechazar')) ?>" data-titulo="Rechazar solicitud"
                                        data-campos="<?= e(json_encode(['idContrato' => $solicitud['idContrato'], 'numeroContrato' => $solicitud['numeroContrato'], 'motivoRechazo' => ''])) ?>"
                                        title="Rechazar" aria-label="Rechazar <?= e($solicitud['numeroContrato']) ?>">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pie-tabla">
                <p class="pie-tabla-texto">
                    Mostrando <?= e($desplazamiento + 1) ?>–<?= e($desplazamiento + count($solicitudes)) ?>
                    de <?= e($total) ?> <?= $total === 1 ? 'solicitud' : 'solicitudes' ?>
                </p>
                <?php
                $rutaPaginacion = '/contratos';
                $parametrosPaginacion = ['tab' => 'solicitudes'];
                require RAIZ . '/app/views/componentes/paginacion.php';
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php $volver = 'solicitudes'; require RAIZ . '/app/views/contratos/_modalRechazo.php'; ?>
