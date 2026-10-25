<?php
/**
 * Registrar entrega (RF-09), Figma "Formulario Entrega": cabecera oscura, datos de
 * quien recibe y, por cada equipo por entregar, su estado al salir. Entregas parciales:
 * se entregan solo los equipos marcados.
 *
 * @var array $contrato  Contrato::detalle()
 * @var array $lineas    Contrato::lineas()
 * @var array $valores   lo escrito en el último envío con errores ([] si no hay)
 * @var array $errores
 */
$primeraVez = !$valores; // sin envío anterior: se marcan todos los equipos que pueden salir
$conObservaciones = false;
?>
<a href="<?= e(url('/contratos/detalle?id=' . $contrato['idContrato'])) ?>" class="enlace-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al contrato</a>

<section class="panel panel-registro panel-registro-oscuro">
    <div class="panel-registro-cabecera">
        <span class="panel-registro-icono" aria-hidden="true"><i class="bi bi-box-seam-fill"></i></span>
        <div>
            <h2 class="panel-registro-titulo">Formulario de entrega</h2>
            <p class="panel-registro-subtitulo">Registro de salida y verificación de estado</p>
        </div>
    </div>

    <div class="panel-cuerpo">
        <form method="post" action="<?= e(url('/entregas/guardar')) ?>" class="d-grid gap-3" novalidate data-enviando="Registrando…">
            <?= csrfCampo() ?>
            <input type="hidden" name="idContrato" value="<?= e($contrato['idContrato']) ?>">
            <p class="mb-0">
                <span class="codigo"><?= e($contrato['numeroContrato']) ?></span> <span class="texto-fuerte"><?= e($contrato['cliente']) ?></span>
                · <?= e(periodo($contrato['fechaInicio'], $contrato['fechaFin'])) ?> · <?= e($contrato['destino'] ?? '') ?>
            </p>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="fecha" class="form-label">Fecha y hora de entrega *</label>
                    <input type="datetime-local" id="fecha" name="fecha" max="<?= e(date('Y-m-d\TH:i')) ?>"
                           class="form-control<?= isset($errores['fecha']) ? ' is-invalid' : '' ?>" value="<?= e($valores['fecha'] ?? date('Y-m-d\TH:i')) ?>">
                    <?php if (isset($errores['fecha'])): ?><div class="invalid-feedback"><?= e($errores['fecha']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-5">
                    <label for="nombreReceptor" class="form-label">Recibe en obra *</label>
                    <input type="text" id="nombreReceptor" name="nombreReceptor" maxlength="160" placeholder="Nombre de quien recibe"
                           class="form-control<?= isset($errores['nombreReceptor']) ? ' is-invalid' : '' ?>" value="<?= e($valores['nombreReceptor'] ?? '') ?>">
                    <?php if (isset($errores['nombreReceptor'])): ?><div class="invalid-feedback"><?= e($errores['nombreReceptor']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label for="documentoReceptor" class="form-label">Documento *</label>
                    <input type="text" id="documentoReceptor" name="documentoReceptor" maxlength="25" placeholder="DUI o carné"
                           class="form-control<?= isset($errores['documentoReceptor']) ? ' is-invalid' : '' ?>" value="<?= e($valores['documentoReceptor'] ?? '') ?>">
                    <?php if (isset($errores['documentoReceptor'])): ?><div class="invalid-feedback"><?= e($errores['documentoReceptor']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label for="observaciones" class="form-label">Observaciones <span class="etiqueta-opcional">(opcional)</span></label>
                    <input type="text" id="observaciones" name="observaciones" maxlength="500" placeholder="Ej. Se entregó con transporte de la empresa"
                           class="form-control<?= isset($errores['observaciones']) ? ' is-invalid' : '' ?>" value="<?= e($valores['observaciones'] ?? '') ?>">
                    <?php if (isset($errores['observaciones'])): ?><div class="invalid-feedback"><?= e($errores['observaciones']) ?></div><?php endif; ?>
                </div>
            </div>

            <p class="form-label mb-0">Equipos por entregar</p>
            <?php foreach ($lineas as $linea): ?>
                <?php if ($linea['fechaEntrega'] !== null) continue; // ya entregado ?>
                <?php $campos = $valores['todos'][$linea['idDetalleContrato']] ?? []; ?>
                <div class="equipo-operacion">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="marcado-<?= e($linea['idDetalleContrato']) ?>"
                               name="equipos[<?= e($linea['idDetalleContrato']) ?>][marcado]" value="1"
                               <?= !$linea['listoParaSalir'] ? 'disabled' : (($primeraVez || !empty($campos['marcado'])) ? 'checked' : '') ?>>
                        <label class="form-check-label" for="marcado-<?= e($linea['idDetalleContrato']) ?>">
                            <span class="texto-fuerte"><?= e($linea['nombre']) ?></span> <span class="codigo"><?= e($linea['codigoInventario']) ?></span>
                            <?= estadoBadge($linea['estadoEquipo']) ?>
                        </label>
                    </div>
                    <?php if ($linea['listoParaSalir']): ?>
                        <?php require RAIZ . '/app/views/operacion/_camposEquipo.php'; ?>
                    <?php else: ?>
                        <p class="form-text mb-0">No puede salir: debe estar Disponible y sin mantenimiento abierto.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="pie-registro">
                <p class="form-text m-0"><span class="marca-obligatoria">*</span> Campos obligatorios. Al entregar, el contrato pasa a Activo y los equipos a Alquilado.</p>
                <div class="d-flex gap-2">
                    <a href="<?= e(url('/contratos/detalle?id=' . $contrato['idContrato'])) ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Registrar entrega</button>
                </div>
            </div>
        </form>
    </div>
</section>
