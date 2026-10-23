<?php
/**
 * Mis alquileres (Figma "Mis-alquileres - Daniel"), con filtro por estado (RF-08).
 *
 * @var array  $contratos  Contrato::buscar()
 * @var string $estado     filtro actual ('' = todos)
 * @var int    $total, $pagina, $totalPaginas, $desplazamiento
 */
?>
<div class="contenedor-cliente contenido-cliente">
    <div class="encabezado-pagina">
        <div>
            <h1 class="encabezado-pagina-titulo">Mis alquileres</h1>
            <p class="encabezado-pagina-texto">Tus solicitudes y contratos, del más reciente al más antiguo.</p>
        </div>
        <form method="get" action="<?= e(url('/cliente/alquileres')) ?>" class="d-flex gap-2">
            <label for="estado" class="visually-hidden">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="">Todos los estados</option>
                <?php foreach (Contrato::ESTADOS as $valor): ?>
                    <option value="<?= e($valor) ?>"<?= $valor === $estado ? ' selected' : '' ?>><?= e($valor) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
        </form>
    </div>

    <section class="panel">
        <div class="panel-cuerpo">
            <?php if (!$contratos): ?>
                <div class="estado-vacio">
                    <span class="estado-vacio-icono"><i class="bi bi-clipboard" aria-hidden="true"></i></span>
                    <p class="estado-vacio-titulo"><?= $estado === '' ? 'Todavía no tienes alquileres' : 'No tienes alquileres en estado ' . e($estado) ?></p>
                    <p class="estado-vacio-texto">Elige equipos en el catálogo y envía tu primera solicitud.</p>
                    <a href="<?= e(url('/cliente/catalogo')) ?>" class="btn btn-primary mt-3">Ir al catálogo</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table tabla-er">
                        <thead>
                        <tr>
                            <th scope="col">Código</th>
                            <th scope="col">Equipo</th>
                            <th scope="col" class="d-none d-md-table-cell">Período</th>
                            <th scope="col" class="text-end">Total</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="col-acciones">Acción</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($contratos as $contrato): ?>
                            <tr>
                                <td><span class="codigo"><?= e($contrato['numeroContrato']) ?></span></td>
                                <td>
                                    <span class="texto-fuerte"><?= resumenEquipos($contrato['primerEquipo'], (int) $contrato['equipos']) ?></span>
                                </td>
                                <td class="d-none d-md-table-cell texto-suave text-nowrap"><?= e(periodo($contrato['fechaInicio'], $contrato['fechaFin'])) ?></td>
                                <td class="text-end texto-fuerte"><?= dinero($contrato['totalEstimado']) ?></td>
                                <td><?= estadoBadge($contrato['estado']) ?></td>
                                <td class="col-acciones">
                                    <a href="<?= e(url('/cliente/alquiler?id=' . $contrato['idContrato'])) ?>" class="btn btn-claro btn-sm">Ver detalle</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pie-tabla">
                    <p class="pie-tabla-texto">
                        Mostrando <?= e($desplazamiento + 1) ?>–<?= e($desplazamiento + count($contratos)) ?> de <?= e($total) ?>
                    </p>
                    <?php
                    $rutaPaginacion = '/cliente/alquileres';
                    $parametrosPaginacion = ['estado' => $estado];
                    require RAIZ . '/app/views/componentes/paginacion.php';
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
