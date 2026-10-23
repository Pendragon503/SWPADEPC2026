<?php
/**
 * Contratos (RF-08), para Administrador y Operador (Figma "alquileres-main" y
 * "Alquiler - Remberto"): filtros por número, cliente, equipo, estado y fechas,
 * listado y paginación.
 *
 * @var array $contratos  Contrato::buscar()
 * @var array $filtros    ['numero', 'cliente', 'equipo', 'estado', 'desde', 'hasta']
 * @var int   $total, $pagina, $totalPaginas, $desplazamiento
 */
?>
<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo">Contratos</h2>
        <p class="encabezado-pagina-texto">Gestiona y consulta los alquileres registrados, del más reciente al más antiguo.</p>
    </div>
</div>

<section class="panel mb-4">
    <div class="panel-cabecera panel-cabecera-linea">
        <h3 class="panel-titulo panel-titulo-mayus"><i class="bi bi-funnel-fill" aria-hidden="true"></i> Filtros de búsqueda</h3>
    </div>
    <div class="panel-cuerpo">
        <form method="get" action="<?= e(url('/contratos')) ?>" class="filtros-contratos" role="search">
            <div>
                <label for="numero" class="form-label">N.º de contrato</label>
                <input type="search" id="numero" name="numero" class="form-control" placeholder="Ej. ALQ-2026-00004" value="<?= e($filtros['numero']) ?>">
            </div>
            <div>
                <label for="cliente" class="form-label">Cliente</label>
                <input type="search" id="cliente" name="cliente" class="form-control" placeholder="Código, nombre o razón social" value="<?= e($filtros['cliente']) ?>">
            </div>
            <div>
                <label for="equipo" class="form-label">Equipo</label>
                <input type="search" id="equipo" name="equipo" class="form-control" placeholder="Nombre o código" value="<?= e($filtros['equipo']) ?>">
            </div>
            <div>
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (Contrato::ESTADOS as $valor): ?>
                        <option value="<?= e($valor) ?>"<?= $valor === $filtros['estado'] ? ' selected' : '' ?>><?= e($valor) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="desde" class="form-label">Desde</label>
                <input type="date" id="desde" name="desde" class="form-control" value="<?= e($filtros['desde']) ?>">
            </div>
            <div>
                <label for="hasta" class="form-label">Hasta</label>
                <input type="date" id="hasta" name="hasta" class="form-control" value="<?= e($filtros['hasta']) ?>">
            </div>
            <div class="filtros-tabla-botones filtros-contratos-botones">
                <a href="<?= e(url('/contratos')) ?>" class="btn btn-secondary">Limpiar</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
            </div>
        </form>
        <p class="form-text mb-0 mt-2">Con "Desde" y "Hasta" salen los contratos cuyo período se cruza con esas fechas.</p>
    </div>
</section>

<section class="panel">
    <div class="panel-cabecera panel-cabecera-linea">
        <h3 class="panel-titulo panel-titulo-mayus"><i class="bi bi-list-ul" aria-hidden="true"></i> Listado de alquileres</h3>
    </div>
    <div class="panel-cuerpo">
        <?php if (!$contratos): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">No hay contratos con esos filtros</p>
                <p class="estado-vacio-texto">Cambia los filtros o presiona "Limpiar".</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col">Número</th>
                        <th scope="col">Cliente / proyecto</th>
                        <th scope="col" class="d-none d-md-table-cell">Equipo</th>
                        <th scope="col" class="d-none d-lg-table-cell">Inicio</th>
                        <th scope="col" class="d-none d-lg-table-cell">Devolución</th>
                        <th scope="col" class="d-none d-xl-table-cell text-end">Días</th>
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
                                <span class="texto-fuerte"><?= e($contrato['cliente']) ?></span>
                                <br><span class="texto-suave small"><?= e($contrato['destino'] ?? $contrato['codigoCliente']) ?></span>
                            </td>
                            <td class="d-none d-md-table-cell"><?= resumenEquipos($contrato['primerEquipo'], (int) $contrato['equipos']) ?></td>
                            <td class="d-none d-lg-table-cell text-nowrap"><?= e(fechaCorta($contrato['fechaInicio'])) ?></td>
                            <td class="d-none d-lg-table-cell text-nowrap"><?= e(fechaCorta($contrato['fechaFin'])) ?></td>
                            <td class="d-none d-xl-table-cell text-end"><?= e($contrato['dias']) ?></td>
                            <td class="text-end texto-fuerte"><?= dinero($contrato['totalEstimado']) ?></td>
                            <td><?= estadoBadge($contrato['estado']) ?></td>
                            <td class="col-acciones">
                                <a href="<?= e(url('/contratos/detalle?id=' . $contrato['idContrato'])) ?>" class="btn btn-claro btn-sm">Ver detalle</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pie-tabla">
                <p class="pie-tabla-texto">
                    Mostrando <?= e($desplazamiento + 1) ?>–<?= e($desplazamiento + count($contratos)) ?>
                    de <?= e($total) ?> <?= $total === 1 ? 'contrato' : 'contratos' ?>
                </p>
                <?php
                $rutaPaginacion = '/contratos';
                $parametrosPaginacion = $filtros;
                require RAIZ . '/app/views/componentes/paginacion.php';
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>
