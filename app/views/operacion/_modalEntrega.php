<?php
/**
 * Modal "Formulario de entrega" (Figma, reemplaza la página anterior; RF-09). Vive en el
 * detalle del contrato. Se entrega un equipo pendiente o "Todos los pendientes" con el mismo
 * estado y los mismos detalles de salida; OperacionController::guardarEntrega lo convierte
 * al formato de siempre y usa Entrega::validar() y registrar() sin cambiar sus reglas.
 *
 * Antes de incluirlo, la vista define:
 *   $contrato  Contrato::detalle()
 *   $entrega   ['pendientes' (líneas listas para salir), 'codigo' (ENT-AAAA-NNN), 'formulario', 'abrir']
 */
$valoresEntrega = $entrega['formulario']['valores'] ?? ['equipo' => (count($entrega['pendientes']) > 1 ? 'todos' : (string) ($entrega['pendientes'][0]['idDetalleContrato'] ?? ''))];
$erroresEntrega = $entrega['formulario']['errores'] ?? [];
$totalPendientes = count($entrega['pendientes']);
$valor = function (string $campo, string $porDefecto = '') use ($valoresEntrega): string {
    return (string) ($valoresEntrega[$campo] ?? $porDefecto);
};
$invalido = function (string $campo) use ($erroresEntrega): string {
    return isset($erroresEntrega[$campo]) ? ' is-invalid' : '';
};
$error = function (string $campo) use ($erroresEntrega): string {
    return isset($erroresEntrega[$campo]) ? '<div class="invalid-feedback">' . e($erroresEntrega[$campo]) . '</div>' : '';
};
// Los "Detalles de salida" se abren solos si el error está en uno de ellos.
$abrirDetalles = (bool) array_intersect(['nivelCombustible', 'lecturaHorometro', 'accesorios', 'observaciones'], array_keys($erroresEntrega));
?>
<div class="modal fade modal-er" id="modalEntrega" tabindex="-1" aria-labelledby="modalEntregaTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $entrega['abrir'] ? '1' : '0' ?>">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="post" action="<?= e(url('/entregas/guardar')) ?>" class="modal-content" novalidate data-enviando="Registrando…">
            <span class="franja-precaucion" aria-hidden="true"></span>
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-box-seam-fill"></i></span>
                <div>
                    <h2 class="modal-title" id="modalEntregaTitulo">Formulario de entrega</h2>
                    <p class="modal-subtitulo">Registro de salida y verificación de estado</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?= csrfCampo() ?>
                <input type="hidden" name="idContrato" value="<?= e($contrato['idContrato']) ?>">
                <?php if (isset($erroresEntrega['general'])): ?>
                    <div class="alert alert-danger mb-0" role="alert"><i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i><span><?= e($erroresEntrega['general']) ?></span></div>
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="idEntrega" class="form-label"><i class="bi bi-hash" aria-hidden="true"></i> ID de la entrega</label>
                        <input type="text" id="idEntrega" class="form-control" readonly value="<?= e($entrega['codigo']) ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="equipoEntrega" class="form-label"><i class="bi bi-key-fill" aria-hidden="true"></i> Equipo entregado</label>
                        <select id="equipoEntrega" name="equipo" class="form-select<?= $invalido('equipo') ?>">
                            <?php if ($totalPendientes > 1): ?>
                                <option value="todos"<?= $valor('equipo') === 'todos' ? ' selected' : '' ?>>Todos los pendientes (<?= $totalPendientes ?>)</option>
                            <?php endif; ?>
                            <?php foreach ($entrega['pendientes'] as $linea): ?>
                                <option value="<?= e($linea['idDetalleContrato']) ?>"<?= $valor('equipo') === (string) $linea['idDetalleContrato'] ? ' selected' : '' ?>>
                                    <?= e($linea['codigoInventario'] . ' · ' . $linea['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= $error('equipo') ?>
                        <?php if (!$totalPendientes): ?>
                            <div class="form-text text-danger">Ningún equipo pendiente puede salir ahora: deben estar Disponibles y sin mantenimiento abierto.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label for="cantidadEntrega" class="form-label"><i class="bi bi-123" aria-hidden="true"></i> Cantidad</label>
                        <?php // 1 equipo, o N con "Todos los pendientes" (data-mostrar-si de app.js). ?>
                        <input type="text" id="cantidadEntrega" class="form-control" readonly value="1" data-mostrar-si="equipo!=todos"<?= $valor('equipo') === 'todos' ? ' hidden' : '' ?>>
                        <input type="text" class="form-control" readonly value="<?= e($totalPendientes) ?>" aria-label="Cantidad" data-mostrar-si="equipo=todos"<?= $valor('equipo') === 'todos' ? '' : ' hidden' ?>>
                    </div>

                    <div class="col-12">
                        <div class="recuadro-estado">
                            <label for="estadoFisico" class="form-label mb-0"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Estado del equipo entregado</label>
                            <select id="estadoFisico" name="estadoFisico" class="form-select w-auto flex-grow-1<?= $invalido('estadoFisico') ?>">
                                <option value="">Seleccione el estado de entrega…</option>
                                <?php foreach (Entrega::ESTADOS_SALIDA as $estado): ?>
                                    <option<?= $valor('estadoFisico') === $estado ? ' selected' : '' ?>><?= e($estado) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $error('estadoFisico') ?>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label for="nombreReceptor" class="form-label"><i class="bi bi-person-fill" aria-hidden="true"></i> Recibe</label>
                        <input type="text" id="nombreReceptor" name="nombreReceptor" maxlength="160" placeholder="Nombre de quien recibe en obra"
                               class="form-control<?= $invalido('nombreReceptor') ?>" value="<?= e($valor('nombreReceptor')) ?>">
                        <?= $error('nombreReceptor') ?>
                    </div>
                    <div class="col-md-3">
                        <label for="documentoReceptor" class="form-label"><i class="bi bi-person-vcard-fill" aria-hidden="true"></i> Documento</label>
                        <input type="text" id="documentoReceptor" name="documentoReceptor" maxlength="25" placeholder="DUI o carné"
                               class="form-control<?= $invalido('documentoReceptor') ?>" value="<?= e($valor('documentoReceptor')) ?>">
                        <?= $error('documentoReceptor') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="fechaEntrega" class="form-label"><i class="bi bi-calendar-event" aria-hidden="true"></i> Fecha</label>
                        <input type="datetime-local" id="fechaEntrega" name="fecha" max="<?= e(date('Y-m-d\TH:i')) ?>"
                               class="form-control<?= $invalido('fecha') ?>" value="<?= e($valor('fecha', date('Y-m-d\TH:i'))) ?>">
                        <?= $error('fecha') ?>
                    </div>
                </div>

                <details class="ficha-plegable"<?= $abrirDetalles ? ' open' : '' ?>>
                    <summary>Detalles de salida <span class="etiqueta-opcional">(opcional)</span></summary>
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <label for="nivelCombustible" class="form-label">Combustible %</label>
                            <input type="number" id="nivelCombustible" name="nivelCombustible" min="0" max="100" step="1"
                                   class="form-control<?= $invalido('nivelCombustible') ?>" value="<?= e($valor('nivelCombustible')) ?>">
                            <?= $error('nivelCombustible') ?>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="lecturaHorometro" class="form-label">Horómetro (h)</label>
                            <input type="number" id="lecturaHorometro" name="lecturaHorometro" min="0" step="0.1"
                                   class="form-control<?= $invalido('lecturaHorometro') ?>" value="<?= e($valor('lecturaHorometro')) ?>">
                            <?= $error('lecturaHorometro') ?>
                        </div>
                        <div class="col-md-6">
                            <label for="accesorios" class="form-label">Accesorios</label>
                            <input type="text" id="accesorios" name="accesorios" maxlength="300" placeholder="Ej. Balde, llave de encendido, extintor"
                                   class="form-control<?= $invalido('accesorios') ?>" value="<?= e($valor('accesorios')) ?>">
                            <?= $error('accesorios') ?>
                        </div>
                        <div class="col-12">
                            <label for="observacionesEntrega" class="form-label">Observaciones</label>
                            <input type="text" id="observacionesEntrega" name="observaciones" maxlength="500" placeholder="Ej. Se entregó con transporte de la empresa"
                                   class="form-control<?= $invalido('observaciones') ?>" value="<?= e($valor('observaciones')) ?>">
                            <?= $error('observaciones') ?>
                        </div>
                        <p class="form-text m-0">Con "Todos los pendientes", estos datos se guardan igual para cada equipo.</p>
                    </div>
                </details>
                <p class="form-text m-0">Al entregar, el contrato pasa a Activo y los equipos a Alquilado.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"<?= $totalPendientes ? '' : ' disabled' ?>><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Registrar entrega</button>
            </div>
        </form>
    </div>
</div>
