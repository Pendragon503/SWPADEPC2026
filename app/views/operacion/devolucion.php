<?php
/**
 * Registrar devolución (RF-10, RF-11), Figma "devoluciones-registrar": fecha y hora,
 * por cada equipo entregado y sin devolver su inspección y, si tiene, sus daños.
 * Se reciben solo los equipos marcados (devoluciones parciales).
 *
 * @var array $contrato     Contrato::detalle()
 * @var array $lineas       Contrato::lineas()
 * @var array $liquidacion  Contrato::liquidacion(): cargos de devoluciones anteriores
 * @var array $valores      lo escrito en el último envío con errores ([] si no hay)
 * @var array $errores
 */
$primeraVez = !$valores;
$conObservaciones = true;
$atrasoHoy = diasAtraso($contrato['fechaFin']);
?>
<a href="<?= e(url('/contratos/detalle?id=' . $contrato['idContrato'])) ?>" class="enlace-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al contrato</a>

<section class="panel panel-registro panel-registro-oscuro">
    <div class="panel-registro-cabecera">
        <span class="panel-registro-icono" aria-hidden="true"><i class="bi bi-arrow-repeat"></i></span>
        <div>
            <h2 class="panel-registro-titulo">Registrar devolución</h2>
            <p class="panel-registro-subtitulo">Revisa cada equipo recibido</p>
        </div>
    </div>

    <div class="panel-cuerpo">
        <form method="post" action="<?= e(url('/devoluciones/guardar')) ?>" class="d-grid gap-3" novalidate data-enviando="Registrando…">
            <?= csrfCampo() ?>
            <input type="hidden" name="idContrato" value="<?= e($contrato['idContrato']) ?>">

            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <p class="form-label mb-1">Alquiler</p>
                    <p class="mb-0"><span class="codigo"><?= e($contrato['numeroContrato']) ?></span> <span class="texto-fuerte"><?= e($contrato['cliente']) ?></span>
                        · devolución pactada el <?= e(fechaCorta($contrato['fechaFin'])) ?></p>
                </div>
                <div class="col-md-4">
                    <label for="fecha" class="form-label">Fecha y hora de recepción *</label>
                    <input type="datetime-local" id="fecha" name="fecha" max="<?= e(date('Y-m-d\TH:i')) ?>"
                           class="form-control<?= isset($errores['fecha']) ? ' is-invalid' : '' ?>" value="<?= e($valores['fecha'] ?? date('Y-m-d\TH:i')) ?>">
                    <?php if (isset($errores['fecha'])): ?><div class="invalid-feedback"><?= e($errores['fecha']) ?></div><?php endif; ?>
                </div>
            </div>

            <p class="form-label mb-0">Equipos por recibir</p>
            <?php foreach ($lineas as $linea): ?>
                <?php if ($linea['fechaEntrega'] === null || $linea['fechaDevolucion'] !== null) continue; // sin entregar o ya devuelto ?>
                <?php
                $id = (int) $linea['idDetalleContrato'];
                $campos = $valores['todos'][$id] ?? [];
                $conDanos = ($campos['tieneDanos'] ?? '') === 'Si';
                ?>
                <div class="equipo-operacion">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="marcado-<?= $id ?>" name="equipos[<?= $id ?>][marcado]" value="1"
                               <?= ($primeraVez || !empty($campos['marcado'])) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="marcado-<?= $id ?>">
                            <span class="texto-fuerte"><?= e($linea['nombre']) ?></span> <span class="codigo"><?= e($linea['codigoInventario']) ?></span>
                            <span class="texto-suave small">Entregado el <?= e(fechaCorta($linea['fechaEntrega'], true)) ?> · <?= dinero($linea['precioDiario']) ?>/día</span>
                        </label>
                    </div>
                    <?php require RAIZ . '/app/views/operacion/_camposEquipo.php'; ?>

                    <div class="row g-2 align-items-end">
                        <div class="col-sm-4 col-lg-3">
                            <label class="form-label" for="danos-<?= $id ?>">¿Tiene daños?</label>
                            <select id="danos-<?= $id ?>" name="equipos[<?= $id ?>][tieneDanos]" class="form-select">
                                <option value="No">No</option>
                                <option value="Si"<?= $conDanos ? ' selected' : '' ?>>Sí: queda En revisión</option>
                            </select>
                        </div>
                    </div>
                    <?php // Solo se ve con "Sí" (data-mostrar-si de app.js). Las filas sin descripción se ignoran. ?>
                    <div class="d-grid gap-2" data-mostrar-si="equipos[<?= $id ?>][tieneDanos]=Si"<?= $conDanos ? '' : ' hidden' ?>>
                        <?php if (isset($errores["equipos[$id][danos]"])): ?>
                            <div class="text-danger small"><?= e($errores["equipos[$id][danos]"]) ?></div>
                        <?php endif; ?>
                        <?php for ($n = 0; $n < Devolucion::FILAS_DANOS; $n++): ?>
                            <?php
                            $dano = $campos['danos'][$n] ?? [];
                            $prefijo = "equipos[$id][danos][$n]";
                            ?>
                            <div class="row g-2">
                                <div class="col-lg-6">
                                    <input type="text" name="<?= e($prefijo) ?>[descripcion]" maxlength="500" aria-label="Descripción del daño <?= $n + 1 ?>"
                                           class="form-control<?= isset($errores[$prefijo . '[descripcion]']) ? ' is-invalid' : '' ?>"
                                           placeholder="Daño <?= $n + 1 ?>: descripción" value="<?= e($dano['descripcion'] ?? '') ?>">
                                </div>
                                <div class="col-5 col-lg-2">
                                    <select name="<?= e($prefijo) ?>[severidad]" aria-label="Severidad del daño <?= $n + 1 ?>"
                                            class="form-select<?= isset($errores[$prefijo . '[severidad]']) ? ' is-invalid' : '' ?>">
                                        <option value="">Severidad</option>
                                        <?php foreach (Devolucion::SEVERIDADES as $severidad): ?>
                                            <option<?= ($dano['severidad'] ?? '') === $severidad ? ' selected' : '' ?>><?= e($severidad) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-4 col-lg-2">
                                    <input type="number" name="<?= e($prefijo) ?>[costo]" min="0" step="0.01" aria-label="Costo estimado del daño <?= $n + 1 ?>"
                                           class="form-control<?= isset($errores[$prefijo . '[costo]']) ? ' is-invalid' : '' ?>"
                                           placeholder="Costo $" value="<?= e($dano['costo'] ?? '') ?>">
                                </div>
                                <div class="col-3 col-lg-2 d-flex align-items-center">
                                    <div class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input" id="cobrar-<?= $id ?>-<?= $n ?>" name="<?= e($prefijo) ?>[cobrar]" value="1"<?= !empty($dano['cobrar']) ? ' checked' : '' ?>>
                                        <label class="form-check-label small" for="cobrar-<?= $id ?>-<?= $n ?>">Cobrar</label>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="alert alert-warning mb-0" role="note">
                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                <span>Si hay daños, regístralos: el equipo queda <strong>En revisión</strong>. Sin daños vuelve a <strong>Disponible</strong>.</span>
            </div>
            <p class="mb-0 small">
                <strong>Atraso a hoy: <?= e($atrasoHoy) ?> <?= $atrasoHoy === 1 ? 'día' : 'días' ?></strong>
                · Penalización por equipo = días de atraso × su tarifa diaria
                · Depósito de garantía: <strong><?= dinero($contrato['depositoGarantia']) ?></strong>
                <?php if ($liquidacion['cargos'] > 0): ?> · Cargos de devoluciones anteriores: <strong><?= dinero($liquidacion['cargos']) ?></strong><?php endif; ?>
            </p>
            <p class="form-text mb-0">La garantía a devolver es el depósito menos la penalización y los daños cobrados al cliente; se muestra al confirmar.</p>

            <div class="pie-registro">
                <p class="form-text m-0"><span class="marca-obligatoria">*</span> Campos obligatorios</p>
                <div class="d-flex gap-2">
                    <a href="<?= e(url('/contratos/detalle?id=' . $contrato['idContrato'])) ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Confirmar recepción</button>
                </div>
            </div>
        </form>
    </div>
</section>
