<?php
/**
 * Daños registrados en las devoluciones, con filtro por estado. Su estado cambia con
 * la orden de mantenimiento del equipo (ver Mantenimiento).
 *
 * @var array  $danos   Mantenimiento::danos()
 * @var string $estado  filtro actual ('' = todos)
 */
?>
<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo">Mantenimiento</h2>
        <p class="encabezado-pagina-texto">Daños detectados al recibir los equipos. Se reparan abriendo una orden de mantenimiento.</p>
    </div>
    <form method="get" action="<?= e(url('/mantenimiento')) ?>" class="d-flex gap-2">
        <input type="hidden" name="tab" value="danos">
        <label for="estado" class="visually-hidden">Estado</label>
        <select id="estado" name="estado" class="form-select">
            <option value="">Todos los estados</option>
            <?php foreach (Mantenimiento::ESTADOS_DANO as $valor): ?>
                <option value="<?= e($valor) ?>"<?= $valor === $estado ? ' selected' : '' ?>><?= strip_tags(estadoBadge($valor)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
    </form>
</div>
<?php $grupo = 'mantenimiento'; $pestana = 'danos'; require RAIZ . '/app/views/componentes/pestanas.php'; ?>

<section class="panel">
    <div class="panel-cuerpo">
        <?php if (!$danos): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">No hay daños<?= $estado !== '' ? ' en ese estado' : '' ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col">Equipo</th>
                        <th scope="col">Daño</th>
                        <th scope="col" class="d-none d-md-table-cell">Contrato</th>
                        <th scope="col" class="d-none d-lg-table-cell">Devuelto</th>
                        <th scope="col" class="text-end">Costo</th>
                        <th scope="col">Estado</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($danos as $dano): ?>
                        <tr>
                            <td><span class="texto-fuerte"><?= e($dano['nombre']) ?></span><br><span class="codigo"><?= e($dano['codigoInventario']) ?></span> <?= estadoBadge($dano['estadoEquipo']) ?></td>
                            <td><?= e($dano['descripcion']) ?><br><span class="texto-suave small"><?= e($dano['severidad']) ?><?= $dano['cargoAlCliente'] ? ' · cobrado al cliente' : '' ?></span></td>
                            <td class="d-none d-md-table-cell"><a href="<?= e(url('/contratos/detalle?id=' . $dano['idContrato'])) ?>" class="codigo"><?= e($dano['numeroContrato']) ?></a></td>
                            <td class="d-none d-lg-table-cell text-nowrap"><?= e(fechaCorta($dano['fechaDevolucion'], true)) ?></td>
                            <td class="text-end"><?= dinero($dano['costoEstimado']) ?></td>
                            <td><?= estadoBadge($dano['estado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
