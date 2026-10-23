<?php
/**
 * "Mi solicitud" (Figma "Solicitar - Daniel"): fechas, equipos con su importe (RF-07),
 * datos de entrega y confirmación. Sin "envío a obra": no existe en la base; se
 * cobra el depósito de garantía.
 *
 * @var array      $solicitud   Solicitud::actual()
 * @var array      $importe     Solicitud::importe()
 * @var string     $solicitante nombre o razón social del cliente
 * @var array|null $formulario  datos y errores del último envío (o null)
 */
$valores = $formulario['valores'] ?? ['destino' => '', 'observaciones' => ''];
$errores = $formulario['errores'] ?? [];
?>
<div class="contenedor-cliente contenido-cliente">
    <div class="encabezado-pagina">
        <div>
            <h1 class="encabezado-pagina-titulo">Solicitud de alquiler</h1>
            <p class="encabezado-pagina-texto">Revisa los equipos y el importe antes de enviar tu solicitud.</p>
        </div>
        <?php if ($importe['lineas']): ?>
            <form method="post" action="<?= e(url('/cliente/solicitud/vaciar')) ?>" data-confirmar="¿Vaciar tu solicitud? Se quitarán todos los equipos.">
                <?= csrfCampo() ?>
                <button type="submit" class="btn btn-secondary"><i class="bi bi-trash" aria-hidden="true"></i> Vaciar solicitud</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!$importe['lineas']): ?>
        <section class="panel">
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-cart3" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">Tu solicitud está vacía</p>
                <p class="estado-vacio-texto">Agrega equipos desde el catálogo o busca los que están libres en tus fechas.</p>
                <div class="d-flex gap-2 mt-3">
                    <a href="<?= e(url('/cliente/catalogo')) ?>" class="btn btn-primary">Ir al catálogo</a>
                    <a href="<?= e(url('/cliente/disponibilidad')) ?>" class="btn btn-secondary">Buscar por fechas</a>
                </div>
            </div>
        </section>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <?php // Varios paneles en la columna: van dentro de un contenedor para que no se estiren al alto de la fila. ?>
                <div class="d-grid gap-4">
                <section class="panel">
                    <div class="panel-cabecera panel-cabecera-linea">
                        <h2 class="panel-titulo panel-titulo-mayus">Fechas del alquiler</h2>
                    </div>
                    <div class="panel-cuerpo">
                        <?php // Cambiar las fechas revisa todos los equipos y quita los que no estén libres. ?>
                        <form method="post" action="<?= e(url('/cliente/solicitud/fechas')) ?>" class="consulta-fechas consulta-fechas-2" novalidate>
                            <?= csrfCampo() ?>
                            <div>
                                <label for="inicio" class="form-label">Fecha de entrega</label>
                                <input type="date" id="inicio" name="inicio" min="<?= e(date('Y-m-d')) ?>" required class="form-control" value="<?= e($solicitud['inicio']) ?>">
                            </div>
                            <div>
                                <label for="fin" class="form-label">Fecha de devolución</label>
                                <input type="date" id="fin" name="fin" min="<?= e(date('Y-m-d')) ?>" required class="form-control" value="<?= e($solicitud['fin']) ?>">
                            </div>
                            <button type="submit" class="btn btn-secondary">Cambiar fechas</button>
                        </form>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-cabecera panel-cabecera-linea">
                        <h2 class="panel-titulo panel-titulo-mayus">Equipos e importe</h2>
                        <a href="<?= e(url('/cliente/disponibilidad?inicio=' . $solicitud['inicio'] . '&fin=' . $solicitud['fin'])) ?>" class="btn btn-claro btn-sm">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar otro equipo
                        </a>
                    </div>
                    <div class="panel-cuerpo">
                        <?php $puedeQuitar = true; require RAIZ . '/app/views/componentes/importe.php'; ?>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-cabecera panel-cabecera-linea">
                        <h2 class="panel-titulo panel-titulo-mayus">Datos de entrega y proyecto</h2>
                    </div>
                    <div class="panel-cuerpo">
                        <?php // data-enviando: al enviar, app.js desactiva el botón para que no se confirme dos veces (RF-15). ?>
                        <form method="post" action="<?= e(url('/cliente/solicitud/enviar')) ?>" class="d-grid gap-3" novalidate
                              data-enviando="Enviando…">
                            <?= csrfCampo() ?>
                            <div>
                                <label for="solicitante" class="form-label">Solicitante / empresa</label>
                                <input type="text" id="solicitante" class="form-control" value="<?= e($solicitante) ?>" readonly>
                            </div>
                            <div>
                                <label for="destino" class="form-label">Dirección de la obra</label>
                                <input type="text" id="destino" name="destino" maxlength="250" required
                                       class="form-control<?= isset($errores['destino']) ? ' is-invalid' : '' ?>"
                                       placeholder="Ej. Calle Los Almendros #45, San Salvador" value="<?= e($valores['destino']) ?>">
                                <?php if (isset($errores['destino'])): ?><div class="invalid-feedback"><?= e($errores['destino']) ?></div><?php endif; ?>
                            </div>
                            <div>
                                <label for="observaciones" class="form-label">Observaciones <span class="etiqueta-opcional">(opcional)</span></label>
                                <textarea id="observaciones" name="observaciones" rows="3" maxlength="500"
                                          class="form-control<?= isset($errores['observaciones']) ? ' is-invalid' : '' ?>"
                                          placeholder="Ej. Acceso por el portón norte; contactar al ingeniero residente"><?= e($valores['observaciones']) ?></textarea>
                                <?php if (isset($errores['observaciones'])): ?><div class="invalid-feedback"><?= e($errores['observaciones']) ?></div><?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary btn-confirmar">
                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Confirmar solicitud de alquiler
                            </button>
                        </form>
                    </div>
                </section>
                </div>
            </div>

            <div class="col-lg-4">
                <div>
                <aside class="panel resumen-alquiler">
                    <div class="panel-cabecera panel-cabecera-linea">
                        <h2 class="panel-titulo panel-titulo-mayus">Resumen del alquiler</h2>
                    </div>
                    <div class="panel-cuerpo">
                        <dl class="resumen-datos">
                            <div><dt>Equipos</dt><dd><?= count($importe['lineas']) ?></dd></div>
                            <div><dt>Período</dt><dd><?= e(periodo($solicitud['inicio'], $solicitud['fin'])) ?></dd></div>
                            <div><dt>Días</dt><dd><?= e($importe['dias']) ?></dd></div>
                            <div><dt>Subtotal</dt><dd><?= dinero($importe['subtotal']) ?></dd></div>
                            <div><dt>Depósito de garantía</dt><dd><?= dinero($importe['deposito']) ?></dd></div>
                            <div class="resumen-total"><dt>Total</dt><dd><?= dinero($importe['total']) ?> USD</dd></div>
                        </dl>
                        <p class="form-text mb-0">El depósito se devuelve al regresar los equipos en buen estado. La solicitud queda Pendiente hasta que la apruebe un operador.</p>
                    </div>
                </aside>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
