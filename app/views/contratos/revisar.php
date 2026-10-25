<?php
/**
 * Nuevo alquiler, paso 2 de 2 (RF-06, RF-07): diseño "Nuevo registro de alquiler".
 * Cabecera naranja; cliente y destino; equipos con su importe; fechas, tarifa diaria,
 * total de días y subtotal de solo lectura; depósito y el recuadro oscuro "TOTAL EST.";
 * observaciones. Todos los montos salen de Solicitud::importe() (precios vigentes).
 *
 * @var array      $solicitud     ['inicio', 'fin', 'equipos']
 * @var array      $importe       Solicitud::importe()
 * @var float      $tarifaDiaria  suma de las tarifas diarias de los equipos
 * @var array      $clientes      Cliente::listarActivos()
 * @var array|null $formulario    datos y errores del último envío (o null)
 * @var string     $urlCambiar    paso 1 con las fechas y los equipos marcados
 */
$valores = $formulario['valores'] ?? ['idCliente' => 0, 'destino' => '', 'observaciones' => ''];
$errores = $formulario['errores'] ?? [];
?>
<a href="<?= e(url($urlCambiar)) ?>" class="enlace-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Cambiar fechas o equipos</a>

<section class="panel panel-registro">
    <div class="panel-registro-cabecera">
        <span class="panel-registro-icono" aria-hidden="true"><i class="bi bi-file-earmark-plus"></i></span>
        <div>
            <h2 class="panel-registro-titulo">Nuevo registro de alquiler</h2>
            <p class="panel-registro-subtitulo">Paso 2 de 2: revisa el importe y registra el contrato</p>
        </div>
    </div>

    <div class="panel-cuerpo">
        <?php // data-enviando: al enviar, app.js desactiva el botón para que no se registre dos veces (RF-15). ?>
        <form method="post" action="<?= e(url('/contratos/registrar')) ?>" class="d-grid gap-3" novalidate data-enviando="Registrando…">
            <?= csrfCampo() ?>
            <input type="hidden" name="inicio" value="<?= e($solicitud['inicio']) ?>">
            <input type="hidden" name="fin" value="<?= e($solicitud['fin']) ?>">
            <?php foreach ($solicitud['equipos'] as $idEquipo): ?>
                <input type="hidden" name="equipos[]" value="<?= e($idEquipo) ?>">
            <?php endforeach; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="idCliente" class="form-label">Cliente <span class="marca-obligatoria" aria-hidden="true">*</span></label>
                    <select id="idCliente" name="idCliente" required class="form-select<?= isset($errores['idCliente']) ? ' is-invalid' : '' ?>">
                        <option value="">Elige el cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= e($cliente['idCliente']) ?>"<?= (int) $cliente['idCliente'] === (int) $valores['idCliente'] ? ' selected' : '' ?>>
                                <?= e($cliente['nombre']) ?> (<?= e($cliente['codigoCliente']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['idCliente'])): ?><div class="invalid-feedback"><?= e($errores['idCliente']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="destino" class="form-label">Proyecto / destino <span class="marca-obligatoria" aria-hidden="true">*</span></label>
                    <input type="text" id="destino" name="destino" maxlength="250" required
                           class="form-control<?= isset($errores['destino']) ? ' is-invalid' : '' ?>"
                           placeholder="Ej. Edificio Las Orquídeas, Calle El Mirador, San Salvador" value="<?= e($valores['destino']) ?>">
                    <?php if (isset($errores['destino'])): ?><div class="invalid-feedback"><?= e($errores['destino']) ?></div><?php endif; ?>
                </div>
            </div>

            <div>
                <p class="form-label mb-1">Equipos disponibles elegidos</p>
                <?php $puedeQuitar = false; $conTotales = false; require RAIZ . '/app/views/componentes/importe.php'; ?>
            </div>

            <div class="row g-3">
                <div class="col-sm-6">
                    <label for="fechaInicio" class="form-label">Fecha de inicio</label>
                    <input type="text" id="fechaInicio" class="form-control campo-calculado" readonly value="<?= e(fechaCorta($solicitud['inicio'])) ?>">
                </div>
                <div class="col-sm-6">
                    <label for="fechaFin" class="form-label">Fecha de devolución</label>
                    <input type="text" id="fechaFin" class="form-control campo-calculado" readonly value="<?= e(fechaCorta($solicitud['fin'])) ?>">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-sm-4">
                    <label for="tarifaDiaria" class="form-label">Tarifa diaria</label>
                    <input type="text" id="tarifaDiaria" class="form-control campo-calculado" readonly value="<?= e(dinero($tarifaDiaria)) ?>">
                </div>
                <div class="col-sm-4">
                    <label for="totalDias" class="form-label">Total días</label>
                    <input type="text" id="totalDias" class="form-control campo-calculado campo-resaltado" readonly value="<?= e($importe['dias']) ?>">
                </div>
                <div class="col-sm-4">
                    <label for="subtotal" class="form-label">Subtotal</label>
                    <input type="text" id="subtotal" class="form-control campo-calculado" readonly value="<?= e(dinero($importe['subtotal'])) ?>">
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-sm-6">
                    <label for="deposito" class="form-label">Depósito de garantía</label>
                    <input type="text" id="deposito" class="form-control campo-calculado" readonly value="<?= e(dinero($importe['deposito'])) ?>">
                </div>
                <div class="col-sm-6">
                    <div class="total-estimado" role="status">
                        <span class="total-estimado-etiqueta">Total est.<br><small>Incluye depósito</small></span>
                        <span class="total-estimado-monto"><?= dinero($importe['total']) ?></span>
                    </div>
                </div>
            </div>

            <div>
                <label for="observaciones" class="form-label">Observaciones <span class="etiqueta-opcional">(opcional)</span></label>
                <textarea id="observaciones" name="observaciones" rows="3" maxlength="500"
                          class="form-control<?= isset($errores['observaciones']) ? ' is-invalid' : '' ?>"
                          placeholder="Ej. Acceso por el portón norte; contactar al ingeniero residente"><?= e($valores['observaciones']) ?></textarea>
                <?php if (isset($errores['observaciones'])): ?><div class="invalid-feedback"><?= e($errores['observaciones']) ?></div><?php endif; ?>
            </div>

            <div class="pie-registro">
                <p class="form-text m-0"><span class="marca-obligatoria">*</span> Campos obligatorios</p>
                <div class="d-flex gap-2">
                    <a href="<?= e(url('/contratos')) ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle" aria-hidden="true"></i> Registrar alquiler</button>
                </div>
            </div>
        </form>
    </div>
</section>
