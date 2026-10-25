<?php
/**
 * Mantenimiento (RF-12): equipos en revisión por daños, filtros, órdenes y los modales
 * de abrir y cerrar una orden. Sin frame disponible: estilo de Inventario.
 *
 * @var array      $ordenes     Mantenimiento::listar()
 * @var array      $elegibles   Mantenimiento::equiposElegibles() [idEquipo => equipo]
 * @var array      $enRevision    los elegibles En revisión (por daños)
 * @var array      $conContratos  los elegibles con contratos Pendientes o Aprobados próximos
 * @var array      $filtros     ['equipo', 'tipo', 'estado']
 * @var array|null $formulario  modal con errores: ['modal' => 'abrir' | 'cerrar', 'valores', 'errores']
 * @var int        $total, $pagina, $totalPaginas, $desplazamiento
 */
$abrir = ($formulario['modal'] ?? '') === 'abrir' ? $formulario : null;
$cerrar = ($formulario['modal'] ?? '') === 'cerrar' ? $formulario : null;
$ahora = date('Y-m-d\TH:i');
$nuevaOrden = ['idEquipo' => '', 'tipo' => 'Preventivo', 'descripcion' => '', 'fechaIngreso' => $ahora, 'fechaSalidaEstimada' => '', 'proveedorServicio' => ''];

// Campo del modal con su error: clase y mensaje.
$invalido = function (?array $modal, string $campo): string {
    return isset($modal['errores'][$campo]) ? ' is-invalid' : '';
};
$mensaje = function (?array $modal, string $campo): string {
    return isset($modal['errores'][$campo]) ? '<div class="invalid-feedback">' . e($modal['errores'][$campo]) . '</div>' : '';
};
?>
<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo">Mantenimiento</h2>
        <p class="encabezado-pagina-texto">Órdenes preventivas y correctivas. Mientras una orden está abierta, el equipo no se puede alquilar.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOrden"
            data-accion="<?= e(url('/mantenimiento/guardar')) ?>" data-titulo="Abrir orden" data-campos="<?= e(json_encode($nuevaOrden)) ?>">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Abrir orden
    </button>
</div>

<?php if ($enRevision): ?>
    <section class="panel mb-4">
        <div class="panel-cabecera">
            <h3 class="panel-titulo">Equipos en revisión por daños</h3>
        </div>
        <div class="panel-cuerpo">
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead><tr><th scope="col">Equipo</th><th scope="col" class="text-end">Daños pendientes</th><th scope="col">Contratos próximos</th><th scope="col" class="col-acciones">Acción</th></tr></thead>
                    <tbody>
                    <?php foreach ($enRevision as $equipo): ?>
                        <tr>
                            <td><span class="codigo"><?= e($equipo['codigoInventario']) ?></span> <span class="texto-fuerte"><?= e($equipo['nombre']) ?></span></td>
                            <td class="text-end"><a href="<?= e(url('/danos?estado=Reportado')) ?>"><?= e($equipo['danosPendientes']) ?></a></td>
                            <td class="small"><?= e($equipo['contratosProximos'] ?? '—') ?></td>
                            <td class="col-acciones">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalOrden"
                                        data-accion="<?= e(url('/mantenimiento/guardar')) ?>" data-titulo="Abrir orden"
                                        data-campos="<?= e(json_encode(['idEquipo' => $equipo['id'], 'tipo' => 'Correctivo', 'descripcion' => 'Reparación de los daños reportados en la devolución'] + $nuevaOrden)) ?>">
                                    Abrir orden
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="panel-cabecera">
        <form method="get" action="<?= e(url('/mantenimiento')) ?>" class="filtros-tabla filtros-tabla-3" role="search">
            <div class="buscador-tabla-campo filtros-tabla-texto">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="equipo" class="visually-hidden">Equipo</label>
                <input type="search" id="equipo" name="equipo" class="form-control" placeholder="Equipo: nombre o código" value="<?= e($filtros['equipo']) ?>">
            </div>
            <div>
                <label for="tipo" class="visually-hidden">Tipo</label>
                <select id="tipo" name="tipo" class="form-select">
                    <option value="">Todos los tipos</option>
                    <?php foreach (Mantenimiento::TIPOS as $tipo): ?>
                        <option<?= $tipo === $filtros['tipo'] ? ' selected' : '' ?>><?= e($tipo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="estado" class="visually-hidden">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="abiertas"<?= $filtros['estado'] === 'abiertas' ? ' selected' : '' ?>>Abiertas</option>
                    <?php foreach (Mantenimiento::ESTADOS as $estado): ?>
                        <option value="<?= e($estado) ?>"<?= $estado === $filtros['estado'] ? ' selected' : '' ?>><?= strip_tags(estadoBadge($estado)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtros-tabla-botones">
                <button type="submit" class="btn btn-secondary">Filtrar</button>
                <a href="<?= e(url('/mantenimiento')) ?>" class="btn btn-claro">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="panel-cuerpo">
        <?php if (!$ordenes): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-wrench-adjustable" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">No hay órdenes con esos filtros</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col">Orden</th>
                        <th scope="col">Equipo</th>
                        <th scope="col" class="d-none d-lg-table-cell">Trabajo</th>
                        <th scope="col" class="d-none d-md-table-cell">Ingreso</th>
                        <th scope="col" class="d-none d-md-table-cell">Salida</th>
                        <th scope="col" class="text-end">Costo</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="col-acciones">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ordenes as $orden): ?>
                        <?php $abierta = in_array($orden['estado'], Mantenimiento::ESTADOS_ABIERTOS, true); ?>
                        <tr>
                            <td><span class="codigo">#<?= e($orden['idMantenimiento']) ?></span><br><span class="texto-suave small"><?= e($orden['tipo']) ?></span></td>
                            <td><span class="texto-fuerte"><?= e($orden['nombre']) ?></span><br><span class="codigo"><?= e($orden['codigoInventario']) ?></span></td>
                            <td class="d-none d-lg-table-cell">
                                <?= e($orden['descripcion']) ?>
                                <?php if ($orden['proveedorServicio'] !== null): ?><br><span class="texto-suave small">Proveedor: <?= e($orden['proveedorServicio']) ?></span><?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell text-nowrap"><?= e(fechaCorta($orden['fechaIngreso'], true)) ?></td>
                            <td class="d-none d-md-table-cell text-nowrap">
                                <?= $orden['fechaSalidaReal'] !== null ? e(fechaCorta($orden['fechaSalidaReal'], true))
                                    : '<span class="texto-suave">Estimada: ' . e(fechaCorta($orden['fechaSalidaEstimada'], true)) . '</span>' ?>
                            </td>
                            <td class="text-end"><?= dinero($orden['costo']) ?></td>
                            <td><?= estadoBadge($orden['estado']) ?></td>
                            <td class="col-acciones text-nowrap">
                                <?php if ($abierta): ?>
                                    <form method="post" action="<?= e(url('/mantenimiento/estado')) ?>" class="d-inline-flex gap-1" data-enviando="">
                                        <?= csrfCampo() ?>
                                        <input type="hidden" name="idMantenimiento" value="<?= e($orden['idMantenimiento']) ?>">
                                        <label class="visually-hidden" for="estado-<?= e($orden['idMantenimiento']) ?>">Estado</label>
                                        <select id="estado-<?= e($orden['idMantenimiento']) ?>" name="estado" class="form-select form-select-sm">
                                            <?php foreach (Mantenimiento::ESTADOS_ABIERTOS as $estado): ?>
                                                <option value="<?= e($estado) ?>"<?= $estado === $orden['estado'] ? ' selected' : '' ?>><?= strip_tags(estadoBadge($estado)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Cambiar estado" aria-label="Cambiar estado de la orden <?= e($orden['idMantenimiento']) ?>"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></button>
                                    </form>
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCierre"
                                            data-accion="<?= e(url('/mantenimiento/cerrar')) ?>" data-titulo="Cerrar orden #<?= e($orden['idMantenimiento']) ?>"
                                            data-campos="<?= e(json_encode(['idMantenimiento' => $orden['idMantenimiento'], 'accion' => 'Finalizado', 'fechaSalidaReal' => $ahora, 'costo' => ''])) ?>">
                                        Cerrar
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="pie-tabla">
                <p class="pie-tabla-texto">Mostrando <?= e($desplazamiento + 1) ?>–<?= e($desplazamiento + count($ordenes)) ?> de <?= e($total) ?> órdenes</p>
                <?php
                $rutaPaginacion = '/mantenimiento';
                $parametrosPaginacion = $filtros;
                require RAIZ . '/app/views/componentes/paginacion.php';
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php // Modal: abrir una orden ?>
<?php $valores = $abrir['valores'] ?? $nuevaOrden; ?>
<div class="modal fade modal-er" id="modalOrden" tabindex="-1" aria-labelledby="modalOrdenTitulo" aria-hidden="true" data-abrir-al-cargar="<?= $abrir ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="post" action="<?= e(url('/mantenimiento/guardar')) ?>" class="modal-content" novalidate data-enviando="Guardando…">
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-wrench-adjustable"></i></span>
                <div>
                    <h2 class="modal-title" id="modalOrdenTitulo">Abrir orden</h2>
                    <p class="modal-subtitulo">El equipo pasará a Mantenimiento</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?= csrfCampo() ?>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="idEquipo" class="form-label"><i class="bi bi-box-seam" aria-hidden="true"></i> Equipo</label>
                        <select id="idEquipo" name="idEquipo" class="form-select<?= $invalido($abrir, 'idEquipo') ?>">
                            <option value="">Elige el equipo</option>
                            <?php foreach ($elegibles as $equipo): ?>
                                <option value="<?= e($equipo['id']) ?>"<?= (string) $equipo['id'] === (string) $valores['idEquipo'] ? ' selected' : '' ?>>
                                    <?= e($equipo['codigoInventario'] . ' · ' . $equipo['nombre'] . ($equipo['estado'] === 'Revision' ? ' (en revisión)' : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= $mensaje($abrir, 'idEquipo') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="tipoOrden" class="form-label"><i class="bi bi-tag" aria-hidden="true"></i> Tipo</label>
                        <select id="tipoOrden" name="tipo" class="form-select<?= $invalido($abrir, 'tipo') ?>">
                            <?php foreach (Mantenimiento::TIPOS as $tipo): ?>
                                <option<?= $tipo === $valores['tipo'] ? ' selected' : '' ?>><?= e($tipo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($conContratos): ?>
                        <div class="col-12">
                            <div class="alert alert-warning mb-0 small" role="note">
                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                <span>Equipos con contratos Pendientes o Aprobados próximos (quizá haya que reprogramarlos o rechazarlos):
                                    <?php foreach ($conContratos as $codigo => $contratos): ?>
                                        <br><strong><?= e($codigo) ?></strong>: <?= e($contratos) ?>
                                    <?php endforeach; ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-4">
                        <label for="fechaIngreso" class="form-label"><i class="bi bi-calendar" aria-hidden="true"></i> Ingreso</label>
                        <input type="datetime-local" id="fechaIngreso" name="fechaIngreso" max="<?= e($ahora) ?>" class="form-control<?= $invalido($abrir, 'fechaIngreso') ?>" value="<?= e($valores['fechaIngreso']) ?>">
                        <?= $mensaje($abrir, 'fechaIngreso') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="fechaSalidaEstimada" class="form-label"><i class="bi bi-calendar-check" aria-hidden="true"></i> Salida estimada</label>
                        <input type="datetime-local" id="fechaSalidaEstimada" name="fechaSalidaEstimada" class="form-control<?= $invalido($abrir, 'fechaSalidaEstimada') ?>" value="<?= e($valores['fechaSalidaEstimada']) ?>">
                        <?= $mensaje($abrir, 'fechaSalidaEstimada') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="proveedorServicio" class="form-label"><i class="bi bi-person-gear" aria-hidden="true"></i> Proveedor</label>
                        <input type="text" id="proveedorServicio" name="proveedorServicio" maxlength="150" placeholder="Opcional" class="form-control<?= $invalido($abrir, 'proveedorServicio') ?>" value="<?= e($valores['proveedorServicio']) ?>">
                        <?= $mensaje($abrir, 'proveedorServicio') ?>
                    </div>
                    <div class="col-12">
                        <label for="descripcion" class="form-label"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Descripción del trabajo</label>
                        <textarea id="descripcion" name="descripcion" rows="2" maxlength="500" class="form-control<?= $invalido($abrir, 'descripcion') ?>"
                                  placeholder="Ej. Cambio de aceite y filtros"><?= e($valores['descripcion']) ?></textarea>
                        <?= $mensaje($abrir, 'descripcion') ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Abrir orden</button>
            </div>
        </form>
    </div>
</div>

<?php // Modal: cerrar una orden (finalizar o cancelar) ?>
<?php $valores = $cerrar['valores'] ?? ['idMantenimiento' => '', 'accion' => 'Finalizado', 'fechaSalidaReal' => $ahora, 'costo' => '']; ?>
<div class="modal fade modal-er" id="modalCierre" tabindex="-1" aria-labelledby="modalCierreTitulo" aria-hidden="true" data-abrir-al-cargar="<?= $cerrar ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= e(url('/mantenimiento/cerrar')) ?>" class="modal-content" novalidate data-enviando="Guardando…">
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-flag"></i></span>
                <div>
                    <h2 class="modal-title" id="modalCierreTitulo">Cerrar orden<?= $cerrar ? ' #' . e($valores['idMantenimiento']) : '' ?></h2>
                    <p class="modal-subtitulo">Finalizar o cancelar</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?= csrfCampo() ?>
                <input type="hidden" name="idMantenimiento" value="<?= e($valores['idMantenimiento']) ?>">
                <div>
                    <label for="accion" class="form-label"><i class="bi bi-flag" aria-hidden="true"></i> Resultado</label>
                    <select id="accion" name="accion" class="form-select">
                        <option value="Finalizado"<?= $valores['accion'] === 'Finalizado' ? ' selected' : '' ?>>Finalizada: el equipo vuelve a Disponible</option>
                        <option value="Cancelado"<?= $valores['accion'] === 'Cancelado' ? ' selected' : '' ?>>Cancelada: vuelve a En revisión si tiene daños</option>
                    </select>
                </div>
                <div class="row g-3" data-mostrar-si="accion=Finalizado"<?= $valores['accion'] === 'Finalizado' ? '' : ' hidden' ?>>
                    <div class="col-7">
                        <label for="fechaSalidaReal" class="form-label"><i class="bi bi-calendar-check" aria-hidden="true"></i> Salida real</label>
                        <input type="datetime-local" id="fechaSalidaReal" name="fechaSalidaReal" max="<?= e($ahora) ?>" class="form-control<?= $invalido($cerrar, 'fechaSalidaReal') ?>" value="<?= e($valores['fechaSalidaReal']) ?>">
                        <?= $mensaje($cerrar, 'fechaSalidaReal') ?>
                    </div>
                    <div class="col-5">
                        <label for="costo" class="form-label"><i class="bi bi-cash" aria-hidden="true"></i> Costo $</label>
                        <input type="number" id="costo" name="costo" min="0" step="0.01" class="form-control<?= $invalido($cerrar, 'costo') ?>" value="<?= e($valores['costo']) ?>">
                        <?= $mensaje($cerrar, 'costo') ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Volver</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>
