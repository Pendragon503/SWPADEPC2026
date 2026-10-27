<?php
/**
 * Historial de un equipo (RF-14): contratos con su entrega y devolución, daños,
 * mantenimientos y cambios de estado (bitácora). Apto para imprimir.
 *
 * @var array      $equipos         Reporte::equipos() para la lista
 * @var array|null $equipo          el equipo elegido (o null)
 * @var array      $contratos       Reporte::contratosDeEquipo()
 * @var array      $danos           Mantenimiento::danos()
 * @var array      $mantenimientos  Mantenimiento::listar()
 * @var array      $bitacora        Equipo::historialEstados()
 */
if ($equipo) {
    $detalleImpresion = 'Historial del equipo ' . $equipo['codigoInventario'] . ' · ' . $equipo['nombre'];
    require RAIZ . '/app/views/reportes/_encabezadoImpresion.php';
}
?>
<a href="<?= e(url('/admin/configuracion?tab=reportes')) ?>" class="enlace-volver no-imprimir"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Ver reportes</a>

<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo">Historial de equipo<?= $equipo ? ': ' . e($equipo['codigoInventario'] . ' · ' . $equipo['nombre']) : '' ?></h2>
        <p class="encabezado-pagina-texto"><?= $equipo ? estadoBadge($equipo['estado']) . ' ' . e($equipo['categoria']) : 'Elige un equipo para ver todo lo que le ha pasado.' ?></p>
    </div>
    <div class="acciones-pagina no-imprimir align-items-start">
        <form method="get" action="<?= e(url('/admin/reportes/equipo')) ?>" class="d-flex gap-2">
            <label for="id" class="visually-hidden">Equipo</label>
            <select id="id" name="id" class="form-select">
                <option value="">Elige el equipo</option>
                <?php foreach ($equipos as $opcion): ?>
                    <option value="<?= e($opcion['idEquipo']) ?>"<?= $equipo && (int) $opcion['idEquipo'] === (int) $equipo['idEquipo'] ? ' selected' : '' ?>><?= e($opcion['codigoInventario'] . ' · ' . $opcion['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Ver</button>
        </form>
        <?php if ($equipo): ?>
            <?php $urlExcel = null; // el historial tiene varias tablas: solo PDF ?>
            <?php require RAIZ . '/app/views/reportes/_descarga.php'; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($equipo): ?>
    <section class="panel mb-4">
        <div class="panel-cabecera"><h3 class="panel-titulo">Contratos, entregas y devoluciones (<?= count($contratos) ?>)</h3></div>
        <div class="panel-cuerpo">
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead><tr><th scope="col">Contrato</th><th scope="col">Cliente</th><th scope="col">Período</th><th scope="col">Entregado</th><th scope="col">Devuelto</th><th scope="col" class="text-end">Importe</th><th scope="col">Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($contratos as $contrato): ?>
                        <tr>
                            <td><a class="codigo" href="<?= e(url('/contratos/detalle?id=' . $contrato['idContrato'])) ?>"><?= e($contrato['numeroContrato']) ?></a></td>
                            <td><?= e($contrato['cliente']) ?></td>
                            <td class="text-nowrap"><?= e(periodo($contrato['fechaInicio'], $contrato['fechaFin'])) ?></td>
                            <td><?= $contrato['fechaEntrega'] !== null ? e(fechaCorta($contrato['fechaEntrega'], true)) . '<br><span class="texto-suave small">' . e($contrato['nombreReceptor']) . '</span>' : '—' ?></td>
                            <td><?= $contrato['fechaDevolucion'] !== null ? e(fechaCorta($contrato['fechaDevolucion'], true)) . ($contrato['diasAtraso'] > 0 ? '<br><span class="text-danger small">' . e($contrato['diasAtraso']) . ' d de atraso · ' . dinero($contrato['penalizacion']) . '</span>' : '') : '—' ?></td>
                            <td class="text-end"><?= dinero($contrato['subtotalLinea']) ?></td>
                            <td><?= estadoBadge($contrato['estado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <section class="panel">
                <div class="panel-cabecera"><h3 class="panel-titulo">Daños (<?= count($danos) ?>)</h3></div>
                <div class="panel-cuerpo">
                    <div class="table-responsive">
                        <table class="table tabla-er">
                            <thead><tr><th scope="col">Daño</th><th scope="col">Contrato</th><th scope="col" class="text-end">Costo</th><th scope="col">Estado</th></tr></thead>
                            <tbody>
                            <?php foreach ($danos as $dano): ?>
                                <tr>
                                    <td><?= e($dano['descripcion']) ?><br><span class="texto-suave small"><?= e($dano['severidad']) ?> · <?= e(fechaCorta($dano['fechaDevolucion'])) ?></span></td>
                                    <td><span class="codigo"><?= e($dano['numeroContrato']) ?></span></td>
                                    <td class="text-end"><?= dinero($dano['costoEstimado']) ?></td>
                                    <td><?= estadoBadge($dano['estado']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-xl-6">
            <section class="panel">
                <div class="panel-cabecera"><h3 class="panel-titulo">Mantenimientos (<?= count($mantenimientos) ?>)</h3></div>
                <div class="panel-cuerpo">
                    <div class="table-responsive">
                        <table class="table tabla-er">
                            <thead><tr><th scope="col">Orden</th><th scope="col">Ingreso / salida</th><th scope="col" class="text-end">Costo</th><th scope="col">Estado</th></tr></thead>
                            <tbody>
                            <?php foreach ($mantenimientos as $orden): ?>
                                <tr>
                                    <td>#<?= e($orden['idMantenimiento']) ?> <?= e($orden['tipo']) ?><br><span class="texto-suave small"><?= e($orden['descripcion']) ?></span></td>
                                    <td class="text-nowrap"><?= e(fechaCorta($orden['fechaIngreso'])) ?><br><?= e(fechaCorta($orden['fechaSalidaReal'])) ?></td>
                                    <td class="text-end"><?= dinero($orden['costo']) ?></td>
                                    <td><?= estadoBadge($orden['estado']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="panel">
        <div class="panel-cabecera"><h3 class="panel-titulo">Cambios de estado (bitácora)</h3></div>
        <div class="panel-cuerpo">
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead><tr><th scope="col">Fecha</th><th scope="col">Cambio</th><th scope="col">Motivo</th><th scope="col">Usuario</th></tr></thead>
                    <tbody>
                    <?php foreach ($bitacora as $registro): ?>
                        <tr>
                            <td class="text-nowrap"><?= e(fechaCorta($registro['fechaCambio'], true)) ?></td>
                            <td class="text-nowrap"><?= $registro['estadoAnterior'] !== null ? estadoBadge($registro['estadoAnterior']) . ' → ' : '' ?><?= estadoBadge($registro['estadoNuevo']) ?></td>
                            <td><?= e($registro['motivo']) ?></td>
                            <td><?= e($registro['usuario']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endif; ?>
