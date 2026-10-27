<?php
/**
 * Modal "Desactivar equipo" (Figma "Formulario Desactivar Equipo"): cabecera roja, el
 * equipo de una lista, el motivo (lista actual) y la fecha de baja de hoy (solo lectura).
 * Lo usan el detalle del equipo (ya seleccionado) y Configuración → Desactivación de equipos.
 * Las reglas son las de siempre (Equipo::validarDesactivacion), en POST /admin/equipos/cambiar-activo.
 *
 * Antes de incluirlo, la vista define:
 *   $equiposActivos   Equipo::activosParaLista()
 *   $idSeleccionado   el equipo que viene elegido (0 = ninguno)
 *   $volverBaja       'detalle' | 'configuracion': a dónde regresar
 *   $formularioBaja   lo escrito y los errores del último envío (o null)
 *   $contratoAbierto  contrato abierto del equipo elegido (solo desde el detalle; null si no hay)
 */
$erroresBaja = $formularioBaja['errores'] ?? [];
$valoresBaja = $formularioBaja['valores'] ?? [];
$idElegido = (int) ($formularioBaja['idEquipo'] ?? $idSeleccionado);
?>
<div class="modal fade modal-er modal-er-peligro" id="modalBaja" tabindex="-1" aria-labelledby="modalBajaTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formularioBaja ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= e(url('/admin/equipos/cambiar-activo')) ?>" class="modal-content" novalidate data-enviando="Desactivando…">
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
                <input type="hidden" name="activar" value="0">
                <input type="hidden" name="volver" value="<?= e($volverBaja) ?>">

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
                        <span>El equipo desactivado no estará disponible para alquileres ni entregas.</span>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="equipoBaja" class="form-label"><i class="bi bi-box-seam" aria-hidden="true"></i> Equipo a desactivar</label>
                    <select id="equipoBaja" name="idEquipo" class="form-select<?= isset($erroresBaja['idEquipo']) ? ' is-invalid' : '' ?>">
                        <option value="">Elige el equipo</option>
                        <?php foreach ($equiposActivos as $opcion): ?>
                            <option value="<?= e($opcion['idEquipo']) ?>"<?= (int) $opcion['idEquipo'] === $idElegido ? ' selected' : '' ?>>
                                <?= e($opcion['codigoInventario'] . ' · ' . $opcion['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erroresBaja['idEquipo'])): ?><div class="invalid-feedback"><?= e($erroresBaja['idEquipo']) ?></div><?php endif; ?>
                </div>
                <div>
                    <label for="motivoBaja" class="form-label"><i class="bi bi-shield-exclamation" aria-hidden="true"></i> Motivo de desactivación</label>
                    <select id="motivoBaja" name="motivo" required class="form-select<?= isset($erroresBaja['motivo']) ? ' is-invalid' : '' ?>">
                        <option value="">Elige el motivo</option>
                        <?php foreach (Equipo::MOTIVOS_BAJA as $motivoBaja): ?>
                            <option value="<?= e($motivoBaja) ?>"<?= ($valoresBaja['motivo'] ?? '') === $motivoBaja ? ' selected' : '' ?>><?= e($motivoBaja) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erroresBaja['motivo'])): ?><div class="invalid-feedback"><?= e($erroresBaja['motivo']) ?></div><?php endif; ?>
                </div>
                <?php // Solo se muestra si el motivo es "Otro" (data-mostrar-si, ver app.js). ?>
                <div data-mostrar-si="motivo=Otro"<?= ($valoresBaja['motivo'] ?? '') === 'Otro' ? '' : ' hidden' ?>>
                    <label for="detalleBaja" class="form-label"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Describe el motivo</label>
                    <textarea id="detalleBaja" name="detalle" rows="2" maxlength="250"
                              class="form-control<?= isset($erroresBaja['detalle']) ? ' is-invalid' : '' ?>"
                              placeholder="Ej. Donado a la municipalidad"><?= e($valoresBaja['detalle'] ?? '') ?></textarea>
                    <?php if (isset($erroresBaja['detalle'])): ?><div class="invalid-feedback"><?= e($erroresBaja['detalle']) ?></div><?php endif; ?>
                </div>
                <div>
                    <label for="fechaBaja" class="form-label"><i class="bi bi-calendar-x" aria-hidden="true"></i> Fecha de baja</label>
                    <input type="text" id="fechaBaja" class="form-control" readonly value="<?= e(date('d/m/Y')) ?>">
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
