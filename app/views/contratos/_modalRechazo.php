<?php
/**
 * Modal "Rechazar solicitud" (.modal-er), compartido por la lista de solicitudes y el
 * detalle de un contrato. El botón que lo abre llena los campos con data-campos
 * (idContrato, numeroContrato, motivoRechazo; ver app.js).
 *
 * Antes de incluirlo, la vista define:
 *   $formularioRechazo  ['campos' => [...], 'errores' => [...]] del último envío con errores, o null
 *   $volver             'solicitudes' | 'detalle': a dónde regresar después de rechazar
 */
$camposRechazo = $formularioRechazo['campos'] ?? ['idContrato' => '', 'numeroContrato' => '', 'motivoRechazo' => ''];
$erroresRechazo = $formularioRechazo['errores'] ?? [];
?>
<div class="modal fade modal-er modal-er-peligro" id="modalRechazo" tabindex="-1" aria-labelledby="modalRechazoTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formularioRechazo ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= e(url('/contratos/rechazar')) ?>" class="modal-content" novalidate data-enviando="Rechazando…">
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-x-octagon"></i></span>
                <div>
                    <h2 class="modal-title" id="modalRechazoTitulo">Rechazar solicitud</h2>
                    <p class="modal-subtitulo">Las fechas quedarán libres</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?= csrfCampo() ?>
                <input type="hidden" name="idContrato" value="<?= e($camposRechazo['idContrato']) ?>">
                <input type="hidden" name="volver" value="<?= e($volver) ?>">

                <div>
                    <label for="numeroRechazo" class="form-label"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Solicitud</label>
                    <input type="text" id="numeroRechazo" name="numeroContrato" class="form-control" readonly value="<?= e($camposRechazo['numeroContrato']) ?>">
                </div>
                <div>
                    <label for="motivoRechazo" class="form-label"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Motivo del rechazo</label>
                    <textarea id="motivoRechazo" name="motivoRechazo" rows="3" maxlength="300" required
                              class="form-control<?= isset($erroresRechazo['motivoRechazo']) ? ' is-invalid' : '' ?>"
                              placeholder="Ej. El equipo no tiene disponibilidad de transporte a esa zona en esas fechas"><?= e($camposRechazo['motivoRechazo']) ?></textarea>
                    <?php if (isset($erroresRechazo['motivoRechazo'])): ?>
                        <div class="invalid-feedback"><?= e($erroresRechazo['motivoRechazo']) ?></div>
                    <?php endif; ?>
                    <p class="form-text mb-0">El cliente verá este motivo en "Mis alquileres".</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-x-octagon" aria-hidden="true"></i> Rechazar</button>
            </div>
        </form>
    </div>
</div>
