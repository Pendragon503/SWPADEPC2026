<?php
/**
 * Detalle de un alquiler del cliente (Figma "Detalle - alquiler - Daniel"): progreso,
 * equipos con su importe, entrega en obra y cancelación mientras esté Pendiente.
 *
 * @var array $contrato       Contrato::detalle()
 * @var array $pasos          Contrato::pasosProgreso() ([] si está Rechazado o Cancelado)
 * @var array $importe        líneas y totales (formato de componentes/importe.php)
 * @var bool  $puedeCancelar
 */
?>
<div class="contenedor-cliente contenido-cliente">
    <a href="<?= e(url('/cliente/alquileres')) ?>" class="enlace-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a mis alquileres</a>

    <div class="encabezado-pagina">
        <div>
            <h1 class="encabezado-pagina-titulo">Detalle del alquiler <span class="codigo"><?= e($contrato['numeroContrato']) ?></span></h1>
            <p class="encabezado-pagina-texto"><?= estadoBadge($contrato['estado']) ?> · Solicitado el <?= e(fechaCorta($contrato['fechaSolicitud'], true)) ?></p>
        </div>
        <?php if ($puedeCancelar): ?>
            <form method="post" action="<?= e(url('/cliente/alquiler/cancelar')) ?>"
                  data-confirmar="¿Cancelar la solicitud <?= e($contrato['numeroContrato']) ?>? Los equipos quedarán libres para otros clientes."
                  data-enviando="Cancelando…">
                <?= csrfCampo() ?>
                <input type="hidden" name="idContrato" value="<?= e($contrato['idContrato']) ?>">
                <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle" aria-hidden="true"></i> Cancelar solicitud</button>
            </form>
        <?php endif; ?>
    </div>

    <section class="panel mb-4">
        <div class="panel-cabecera panel-cabecera-linea">
            <h2 class="panel-titulo panel-titulo-mayus">Progreso de la solicitud</h2>
        </div>
        <div class="panel-cuerpo">
            <?php if ($pasos): ?>
                <?php require RAIZ . '/app/views/componentes/progresoContrato.php'; ?>
            <?php elseif ($contrato['estado'] === 'Rechazado'): ?>
                <div class="alert alert-danger mb-0" role="status">
                    <i class="bi bi-x-octagon-fill" aria-hidden="true"></i>
                    <span><strong>Solicitud rechazada.</strong> Motivo: <?= e($contrato['motivoRechazo']) ?></span>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-0" role="status">
                    <i class="bi bi-slash-circle" aria-hidden="true"></i>
                    <span><strong>Solicitud cancelada.</strong> Los equipos quedaron libres en esas fechas.</span>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <section class="panel">
                <div class="panel-cabecera panel-cabecera-linea">
                    <h2 class="panel-titulo panel-titulo-mayus">Período</h2>
                </div>
                <div class="panel-cuerpo">
                    <dl class="resumen-datos mb-0">
                        <div><dt>Fechas contratadas</dt><dd><?= e(periodo($contrato['fechaInicio'], $contrato['fechaFin'])) ?></dd></div>
                        <div><dt>Equipos</dt><dd><?= count($importe['lineas']) ?></dd></div>
                        <?php if ($contrato['fechaAprobacion'] !== null): ?>
                            <div><dt>Aprobado</dt><dd><?= e(fechaCorta($contrato['fechaAprobacion'], true)) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                </div>
            </section>
        </div>
        <div class="col-lg-6">
            <section class="panel">
                <div class="panel-cabecera panel-cabecera-linea">
                    <h2 class="panel-titulo panel-titulo-mayus">Entrega en obra</h2>
                </div>
                <div class="panel-cuerpo">
                    <dl class="resumen-datos mb-0">
                        <div><dt>Ubicación</dt><dd><?= e($contrato['destino'] ?? '—') ?></dd></div>
                        <div><dt>Entregado</dt><dd><?= $contrato['fechaEntrega'] !== null ? e(fechaCorta($contrato['fechaEntrega'], true)) : 'Todavía no' ?></dd></div>
                        <?php if ($contrato['recibidoPor'] !== null): ?>
                            <div><dt>Recibió</dt><dd><?= e($contrato['recibidoPor']) ?></dd></div>
                        <?php endif; ?>
                        <?php if ($contrato['observaciones'] !== null): ?>
                            <div><dt>Observaciones</dt><dd><?= e($contrato['observaciones']) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                </div>
            </section>
        </div>
    </div>

    <section class="panel">
        <div class="panel-cabecera panel-cabecera-linea">
            <h2 class="panel-titulo panel-titulo-mayus">Equipos e importe</h2>
        </div>
        <div class="panel-cuerpo">
            <?php $puedeQuitar = false; require RAIZ . '/app/views/componentes/importe.php'; ?>
        </div>
    </section>
</div>
