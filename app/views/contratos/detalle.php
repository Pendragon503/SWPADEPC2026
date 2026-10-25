<?php
/**
 * Detalle de un contrato (GET /contratos/detalle?id=9), para Administrador y Operador:
 * cliente, entrega, seguimiento con su progreso y equipos con su importe. No tiene
 * frame en Figma: sigue el estilo del detalle de Inventario (panel, ficha-datos).
 * Las entregas y devoluciones (parte 2 de Operación) se agregarán aquí.
 *
 * @var array $contrato  Contrato::detalle()
 * @var array $pasos     Contrato::pasosProgreso() ([] si está Rechazado o Cancelado)
 * @var array      $importe            Contrato::importe()
 * @var bool       $puedeGestionar     true si sigue Pendiente (aprobar o rechazar)
 * @var array|null $formularioRechazo  modal de rechazo con errores (o null)
 */
?>
<a href="<?= e(url('/contratos')) ?>" class="enlace-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a contratos</a>

<div class="encabezado-pagina">
    <div>
        <p class="mb-1"><span class="codigo"><?= e($contrato['numeroContrato']) ?></span></p>
        <h2 class="encabezado-pagina-titulo"><?= e($contrato['cliente']) ?></h2>
        <p class="encabezado-pagina-texto">
            <?= estadoBadge($contrato['estado']) ?>
            <span class="ms-1">Solicitado el <?= e(fechaCorta($contrato['fechaSolicitud'], true)) ?></span>
        </p>
    </div>
    <?php if ($puedeGestionar): ?>
        <div class="acciones-pagina">
            <button type="button" class="btn btn-danger"
                    data-bs-toggle="modal" data-bs-target="#modalRechazo"
                    data-accion="<?= e(url('/contratos/rechazar')) ?>" data-titulo="Rechazar solicitud"
                    data-campos="<?= e(json_encode(['idContrato' => $contrato['idContrato'], 'numeroContrato' => $contrato['numeroContrato'], 'motivoRechazo' => ''])) ?>">
                <i class="bi bi-x-octagon" aria-hidden="true"></i> Rechazar
            </button>
            <form method="post" action="<?= e(url('/contratos/aprobar')) ?>"
                  data-confirmar="¿Aprobar <?= e($contrato['numeroContrato']) ?>? Se revisará otra vez que cada equipo siga libre."
                  data-enviando="Aprobando…">
                <?= csrfCampo() ?>
                <input type="hidden" name="idContrato" value="<?= e($contrato['idContrato']) ?>">
                <input type="hidden" name="volver" value="detalle">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle" aria-hidden="true"></i> Aprobar</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php if ($contrato['estado'] === 'Rechazado'): ?>
    <div class="alert alert-danger" role="status">
        <i class="bi bi-x-octagon-fill" aria-hidden="true"></i>
        <span><strong>Solicitud rechazada<?= $contrato['gestionadoPor'] !== null ? ' por ' . e($contrato['gestionadoPor']) : '' ?>.</strong>
            Motivo: <?= e($contrato['motivoRechazo']) ?></span>
    </div>
<?php elseif ($contrato['estado'] === 'Cancelado'): ?>
    <div class="alert alert-warning" role="status">
        <i class="bi bi-slash-circle" aria-hidden="true"></i>
        <span><strong>El cliente canceló la solicitud.</strong> Los equipos quedaron libres en esas fechas.</span>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-xl-7">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Cliente y entrega</h3>
            </div>
            <div class="panel-cuerpo">
                <dl class="ficha-datos">
                    <div><dt>Código</dt><dd><span class="codigo"><?= e($contrato['codigoCliente'] ?? '—') ?></span></dd></div>
                    <div><dt>Tipo</dt><dd><?= e(Cliente::TIPOS[$contrato['tipoPersona']] ?? $contrato['tipoPersona']) ?></dd></div>
                    <div><dt>Documento</dt><dd><?= e($contrato['documento'] ?? '—') ?></dd></div>
                    <div><dt>Teléfono</dt><dd><?= e($contrato['telefono'] ?? '—') ?></dd></div>
                    <div><dt>Correo</dt><dd><?= e($contrato['correo']) ?></dd></div>
                </dl>
                <dl class="ficha-textos">
                    <div><dt>Dirección de la obra</dt><dd><?= e($contrato['destino'] ?? '—') ?></dd></div>
                    <div><dt>Observaciones</dt><dd><?= $contrato['observaciones'] !== null ? nl2br(e($contrato['observaciones'])) : '<span class="texto-suave">—</span>' ?></dd></div>
                </dl>
            </div>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Seguimiento</h3>
            </div>
            <div class="panel-cuerpo">
                <?php if ($pasos): ?>
                    <div class="mb-4"><?php require RAIZ . '/app/views/componentes/progresoContrato.php'; ?></div>
                <?php endif; ?>
                <dl class="ficha-datos">
                    <div><dt>Período</dt><dd><?= e(periodo($contrato['fechaInicio'], $contrato['fechaFin'])) ?></dd></div>
                    <div><dt>Días</dt><dd><?= e($contrato['dias']) ?></dd></div>
                    <div><dt>Aprobado</dt><dd><?= e(fechaCorta($contrato['fechaAprobacion'], true)) ?></dd></div>
                    <div><dt>Gestionado por</dt><dd><?= e($contrato['gestionadoPor'] ?? '—') ?></dd></div>
                    <div><dt>Entregado</dt><dd><?= $contrato['fechaEntrega'] !== null ? e(fechaCorta($contrato['fechaEntrega'], true)) : 'Todavía no' ?></dd></div>
                    <div><dt>Recibió</dt><dd><?= e($contrato['recibidoPor'] ?? '—') ?></dd></div>
                </dl>
            </div>
        </section>
    </div>
</div>

<section class="panel">
    <div class="panel-cabecera">
        <h3 class="panel-titulo">Equipos e importe</h3>
    </div>
    <div class="panel-cuerpo">
        <?php $puedeQuitar = false; require RAIZ . '/app/views/componentes/importe.php'; ?>
    </div>
</section>

<?php if ($puedeGestionar): ?>
    <?php $volver = 'detalle'; require RAIZ . '/app/views/contratos/_modalRechazo.php'; ?>
<?php endif; ?>
