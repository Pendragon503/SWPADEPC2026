<?php
/**
 * Detalle de un equipo (GET /admin/equipos/detalle?id=5): ficha técnica,
 * fotografías, tarifas e historial de estados. No tiene frame en Figma: usa los
 * componentes base (panel, tabla-er, estadoBadge, modal-er).
 *
 * @var array      $equipo
 * @var array      $historial         bitácora, del más reciente al más antiguo
 * @var array|null $contratoAbierto   contrato Pendiente/Aprobado/Activo sin devolver, o null
 * @var array      $categorias
 * @var array|null $formulario        modal de editar con errores
 * @var array|null $formularioEstado  modal de cambiar estado con errores
 * @var array|null $formularioBaja    modal de baja con errores
 */
$idEquipo = (int) $equipo['idEquipo'];
$activo = (bool) $equipo['activo'];
$urlDetalle = url('/admin/equipos/detalle?id=' . $idEquipo);

// Si está dado de baja, la "fecha de baja" sale de la bitácora: el último registro de baja.
$baja = null;
if (!$activo) {
    foreach ($historial as $registro) {
        if (strpos($registro['motivo'], 'Baja del equipo') === 0) {
            $baja = $registro;
            break;
        }
    }
}

// El estado solo se cambia a mano si el equipo está activo y no está alquilado.
$puedeCambiarEstado = $activo && $equipo['estado'] !== 'Alquilado';

// Texto con saltos de línea, escapado: e() primero y luego nl2br().
$parrafo = function (?string $texto): string {
    return $texto === null || $texto === '' ? '<span class="texto-suave">—</span>' : nl2br(e($texto));
};
?>
<a href="<?= e(url('/admin/equipos')) ?>" class="enlace-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al inventario</a>

<div class="encabezado-pagina">
    <div>
        <p class="mb-1"><span class="codigo"><?= e($equipo['codigoInventario']) ?></span></p>
        <h2 class="encabezado-pagina-titulo"><?= e($equipo['nombre']) ?></h2>
        <p class="encabezado-pagina-texto">
            <?= estadoBadge($equipo['estado']) ?>
            <?php if (!$activo): ?><?= estadoBadge('Inactivo') ?><?php endif; ?>
            <span class="ms-1"><?= e($equipo['categoria']) ?></span>
        </p>
    </div>
    <div class="acciones-pagina">
        <button type="button" class="btn btn-secondary"
                data-bs-toggle="modal" data-bs-target="#modalEquipo"
                data-accion="<?= e(url('/admin/equipos/actualizar')) ?>"
                data-titulo="Editar equipo" data-modo="editar"
                data-campos="<?= e(json_encode(Equipo::camposFormulario($equipo))) ?>">
            <i class="bi bi-pencil-fill" aria-hidden="true"></i> Editar
        </button>
        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalEstado"
                <?= $puedeCambiarEstado ? '' : 'disabled' ?>>
            <i class="bi bi-activity" aria-hidden="true"></i> Cambiar estado
        </button>
        <?php if ($activo): ?>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalBaja">
                <i class="bi bi-slash-circle" aria-hidden="true"></i> Desactivar
            </button>
        <?php else: ?>
            <form method="post" action="<?= e(url('/admin/equipos/cambiar-activo')) ?>" class="d-inline"
                  data-confirmar="¿Reactivar <?= e($equipo['codigoInventario']) ?>? Volverá a estar Disponible.">
                <?= csrfCampo() ?>
                <input type="hidden" name="idEquipo" value="<?= e($idEquipo) ?>">
                <input type="hidden" name="activar" value="1">
                <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reactivar</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!$activo): ?>
    <div class="alert alert-warning" role="status">
        <i class="bi bi-archive-fill" aria-hidden="true"></i>
        <span>
            <?php if ($baja !== null): ?>
                <strong>Dado de baja el <?= e(fechaCorta($baja['fechaCambio'], true)) ?></strong> por <?= e($baja['usuario']) ?>.
                <?= e($baja['motivo']) ?>. No aparece en el catálogo ni se puede alquilar.
            <?php else: ?>
                <strong>Equipo dado de baja.</strong> No aparece en el catálogo ni se puede alquilar.
            <?php endif; ?>
        </span>
    </div>
<?php elseif ($equipo['estado'] === 'Alquilado'): ?>
    <div class="alert alert-info" role="status">
        <i class="bi bi-truck" aria-hidden="true"></i>
        <span>El equipo está alquilado: su estado cambia cuando Operación registre la devolución.</span>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-12" id="ficha">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Ficha técnica</h3>
            </div>
            <div class="panel-cuerpo">
                <dl class="ficha-datos">
                    <div><dt>Categoría</dt><dd><?= e($equipo['categoria']) ?><?= $equipo['categoriaActiva'] ? '' : ' <span class="texto-suave">(inactiva)</span>' ?></dd></div>
                    <div><dt>Marca</dt><dd><?= e($equipo['marca'] ?? '—') ?></dd></div>
                    <div><dt>Modelo</dt><dd><?= e($equipo['modelo'] ?? '—') ?></dd></div>
                    <div><dt>Número de serie</dt><dd><?= e($equipo['numeroSerie'] ?? '—') ?></dd></div>
                    <div><dt>Fecha de compra</dt><dd><?= e(fechaCorta($equipo['fechaAdquisicion'])) ?></dd></div>
                    <div><dt>Valor de referencia</dt><dd><?= dinero($equipo['valorReferencia']) ?></dd></div>
                    <div><dt>Tarifa diaria vigente</dt><dd><?= dinero($equipo['precioDiario']) ?></dd></div>
                    <div><dt>Depósito sugerido</dt><dd><?= dinero($equipo['depositoSugerido']) ?></dd></div>
                </dl>
                <dl class="ficha-textos">
                    <div><dt>Descripción</dt><dd><?= $parrafo($equipo['descripcion']) ?></dd></div>
                    <div><dt>Características técnicas</dt><dd><?= $parrafo($equipo['caracteristicasTecnicas']) ?></dd></div>
                    <div><dt>Condiciones de uso</dt><dd><?= $parrafo($equipo['condicionesUso']) ?></dd></div>
                </dl>
            </div>
        </section>
    </div>
</div>

<section class="panel" id="historial">
    <div class="panel-cabecera">
        <h3 class="panel-titulo">Historial de estados</h3>
        <span class="texto-suave small">Bitácora automática: cada cambio queda con fecha, usuario y motivo.</span>
    </div>
    <div class="panel-cuerpo">
        <div class="table-responsive">
            <table class="table tabla-er">
                <thead>
                <tr>
                    <th scope="col">Fecha</th>
                    <th scope="col">Cambio</th>
                    <th scope="col">Motivo</th>
                    <th scope="col" class="d-none d-md-table-cell">Usuario</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($historial as $registro): ?>
                    <tr>
                        <td class="text-nowrap"><?= e(fechaCorta($registro['fechaCambio'], true)) ?></td>
                        <td class="text-nowrap">
                            <?php if ($registro['estadoAnterior'] !== null && $registro['estadoAnterior'] !== $registro['estadoNuevo']): ?>
                                <?= estadoBadge($registro['estadoAnterior']) ?> <i class="bi bi-arrow-right texto-suave" aria-label="a"></i>
                            <?php endif; ?>
                            <?= estadoBadge($registro['estadoNuevo']) ?>
                        </td>
                        <td><?= e($registro['motivo']) ?></td>
                        <td class="d-none d-md-table-cell"><?= e($registro['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php
// ---------------------------------------------------------------------
// Modales
// ---------------------------------------------------------------------

// Editar (el mismo modal del listado, pero vuelve al detalle).
$origen = 'detalle';
$volver = '';
require RAIZ . '/app/views/equipos/_formulario.php';

$erroresEstado = $formularioEstado['errores'] ?? [];
$erroresBaja = $formularioBaja['errores'] ?? [];
?>

<?php // Cambiar estado: solo los estados manuales; el motivo queda en la bitácora. ?>
<div class="modal fade modal-er" id="modalEstado" tabindex="-1" aria-labelledby="modalEstadoTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formularioEstado ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= e(url('/admin/equipos/cambiar-estado')) ?>" class="modal-content" novalidate>
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-activity"></i></span>
                <div>
                    <h2 class="modal-title" id="modalEstadoTitulo">Cambiar estado</h2>
                    <p class="modal-subtitulo"><?= e($equipo['codigoInventario']) ?> · ahora <?= e(Equipo::ESTADOS[$equipo['estado']]) ?></p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?= csrfCampo() ?>
                <input type="hidden" name="idEquipo" value="<?= e($idEquipo) ?>">
                <div>
                    <label for="estadoNuevo" class="form-label"><i class="bi bi-activity" aria-hidden="true"></i> Nuevo estado</label>
                    <select id="estadoNuevo" name="estado" required class="form-select<?= isset($erroresEstado['estado']) ? ' is-invalid' : '' ?>">
                        <option value="">Elige el nuevo estado</option>
                        <?php foreach (Equipo::ESTADOS_MANUALES as $valorEstado): ?>
                            <?php if ($valorEstado !== $equipo['estado']): ?>
                                <option value="<?= e($valorEstado) ?>"<?= ($formularioEstado['valores']['estado'] ?? '') === $valorEstado ? ' selected' : '' ?>>
                                    <?= e(Equipo::ESTADOS[$valorEstado]) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erroresEstado['estado'])): ?>
                        <div class="invalid-feedback"><?= e($erroresEstado['estado']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">"Alquilado" no se elige aquí: lo pone el módulo de Operación.</div>
                </div>
                <div>
                    <label for="motivoEstado" class="form-label"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Motivo</label>
                    <textarea id="motivoEstado" name="motivo" rows="3" maxlength="300" required
                              class="form-control<?= isset($erroresEstado['motivo']) ? ' is-invalid' : '' ?>"
                              placeholder="Ej. Revisión de frenos después de la devolución"><?= e($formularioEstado['valores']['motivo'] ?? '') ?></textarea>
                    <?php if (isset($erroresEstado['motivo'])): ?>
                        <div class="invalid-feedback"><?= e($erroresEstado['motivo']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Cambiar</button>
            </div>
        </form>
    </div>
</div>

<?php // Baja (desactivar): Figma "Formulario Desactivar Equipo" (53:1052). ?>
<div class="modal fade modal-er modal-er-peligro" id="modalBaja" tabindex="-1" aria-labelledby="modalBajaTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formularioBaja ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= e(url('/admin/equipos/cambiar-activo')) ?>" class="modal-content" novalidate>
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-slash-circle"></i></span>
                <div>
                    <h2 class="modal-title" id="modalBajaTitulo">Desactivar equipo</h2>
                    <p class="modal-subtitulo">Baja de activos operativos</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?= csrfCampo() ?>
                <input type="hidden" name="idEquipo" value="<?= e($idEquipo) ?>">
                <input type="hidden" name="activar" value="0">

                <?php if ($contratoAbierto !== null): ?>
                    <div class="alert alert-danger mb-0" role="alert">
                        <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                        <span>No se puede desactivar: el equipo está en el contrato
                            <strong><?= e($contratoAbierto['numeroContrato']) ?></strong> (<?= e($contratoAbierto['estado']) ?>).
                            Espera a que termine o quítalo de ese contrato primero.</span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger mb-0" role="note">
                        <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                        <span>El equipo desactivado quedará <strong>Fuera de servicio</strong> y no estará disponible
                            para alquileres ni entregas. La baja queda en el historial con fecha y usuario.</span>
                    </div>
                <?php endif; ?>

                <div>
                    <p class="form-label mb-1"><i class="bi bi-box-seam" aria-hidden="true"></i> Equipo a desactivar</p>
                    <p class="mb-0"><span class="codigo"><?= e($equipo['codigoInventario']) ?></span> <?= e($equipo['nombre']) ?></p>
                </div>
                <div>
                    <label for="motivoBaja" class="form-label"><i class="bi bi-shield-exclamation" aria-hidden="true"></i> Motivo de la baja</label>
                    <select id="motivoBaja" name="motivo" required class="form-select<?= isset($erroresBaja['motivo']) ? ' is-invalid' : '' ?>">
                        <option value="">Elige el motivo</option>
                        <?php foreach (Equipo::MOTIVOS_BAJA as $motivoBaja): ?>
                            <option value="<?= e($motivoBaja) ?>"<?= ($formularioBaja['valores']['motivo'] ?? '') === $motivoBaja ? ' selected' : '' ?>><?= e($motivoBaja) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erroresBaja['motivo'])): ?>
                        <div class="invalid-feedback"><?= e($erroresBaja['motivo']) ?></div>
                    <?php endif; ?>
                </div>
                <?php // Solo se muestra si el motivo es "Otro" (data-mostrar-si, ver app.js). ?>
                <div data-mostrar-si="motivo=Otro"<?= ($formularioBaja['valores']['motivo'] ?? '') === 'Otro' ? '' : ' hidden' ?>>
                    <label for="detalleBaja" class="form-label"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Describe el motivo</label>
                    <textarea id="detalleBaja" name="detalle" rows="2" maxlength="250"
                              class="form-control<?= isset($erroresBaja['detalle']) ? ' is-invalid' : '' ?>"
                              placeholder="Ej. Donado a la municipalidad"><?= e($formularioBaja['valores']['detalle'] ?? '') ?></textarea>
                    <?php if (isset($erroresBaja['detalle'])): ?>
                        <div class="invalid-feedback"><?= e($erroresBaja['detalle']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger"<?= $contratoAbierto !== null ? ' disabled' : '' ?>>
                    <i class="bi bi-slash-circle" aria-hidden="true"></i> Desactivar
                </button>
            </div>
        </form>
    </div>
</div>
