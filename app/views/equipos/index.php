<?php
/**
 * Inventario de equipos: indicadores, filtros, listado y paginación.
 * Diseño: Figma "Equipos Carlos" (38:2), adaptado a los componentes base.
 *
 * @var array      $equipos
 * @var array      $indicadores  Indicador::resumenGeneral()
 * @var array      $categorias   todas (activas e inactivas), para el filtro
 * @var array      $filtros      ['buscar', 'idCategoria', 'estado', 'activo']
 * @var int        $pagina
 * @var int        $totalPaginas
 * @var int        $total
 * @var int        $desplazamiento
 * @var array|null $formulario   formulario del modal con errores (o null)
 */

// Filtros y página actuales en forma de query string. Los formularios POST lo envían
// en el campo oculto "volver" para regresar al mismo lugar del listado.
$parametrosListado = $filtros + ['pagina' => $pagina];
$volver = http_build_query($parametrosListado);

// [etiqueta, valor, icono, color]
$tarjetas = [
    ['Equipos totales', $indicadores['equiposTotales'], 'bi-boxes', 'naranja'],
    ['Disponibles', $indicadores['disponibles'], 'bi-check-circle-fill', 'verde'],
    ['Alquilados', $indicadores['alquilados'], 'bi-truck', 'naranja'],
    ['En mantenimiento', $indicadores['enMantenimiento'], 'bi-wrench-adjustable', 'azul'],
];
?>
<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo">Inventario de equipos</h2>
        <p class="encabezado-pagina-texto">Cada fila es una unidad física, con su propio código de inventario.</p>
    </div>
    <?php // Abre el modal vacío en modo "crear" (ver app.js). ?>
    <button type="button" class="btn btn-primary"
            data-bs-toggle="modal" data-bs-target="#modalEquipo"
            data-accion="<?= e(url('/admin/equipos/guardar')) ?>"
            data-titulo="Registrar equipo" data-modo="crear"
            data-campos="<?= e(json_encode(array_fill_keys(Equipo::CAMPOS_FORMULARIO, ''))) ?>">
        <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Registrar equipo
    </button>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($tarjetas as [$etiqueta, $valor, $icono, $color]): ?>
        <div class="col-6 col-xl-3">
            <div class="indicador">
                <div class="indicador-cabecera">
                    <span class="indicador-etiqueta"><?= e($etiqueta) ?></span>
                    <span class="indicador-icono icono-<?= e($color) ?>"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i></span>
                </div>
                <div class="indicador-pie">
                    <span class="indicador-valor"><?= e($valor) ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<section class="panel">
    <div class="panel-cabecera">
        <?php // Filtros con GET: quedan en la URL, así se puede recargar o compartir el listado filtrado. ?>
        <form method="get" action="<?= e(url('/admin/equipos')) ?>" class="filtros-tabla" role="search">
            <div class="buscador-tabla-campo filtros-tabla-texto">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="buscar" class="visually-hidden">Buscar equipos</label>
                <input type="search" id="buscar" name="buscar" class="form-control"
                       placeholder="Código, nombre o número de serie..." value="<?= e($filtros['buscar']) ?>">
            </div>
            <div>
                <label for="filtroCategoria" class="visually-hidden">Categoría</label>
                <select id="filtroCategoria" name="idCategoria" class="form-select">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= e($categoria['idCategoria']) ?>"<?= (int) $categoria['idCategoria'] === $filtros['idCategoria'] ? ' selected' : '' ?>>
                            <?= e($categoria['nombre']) ?><?= $categoria['activa'] ? '' : ' (inactiva)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filtroEstado" class="visually-hidden">Estado</label>
                <select id="filtroEstado" name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <?php foreach (Equipo::ESTADOS as $valor => $texto): ?>
                        <option value="<?= e($valor) ?>"<?= $valor === $filtros['estado'] ? ' selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filtroActivo" class="visually-hidden">Activos o dados de baja</label>
                <select id="filtroActivo" name="activo" class="form-select">
                    <option value="1"<?= $filtros['activo'] === '1' ? ' selected' : '' ?>>Activos</option>
                    <option value="0"<?= $filtros['activo'] === '0' ? ' selected' : '' ?>>Dados de baja</option>
                    <option value="todos"<?= $filtros['activo'] === 'todos' ? ' selected' : '' ?>>Todos</option>
                </select>
            </div>
            <div class="filtros-tabla-botones">
                <button type="submit" class="btn btn-secondary">Filtrar</button>
                <a href="<?= e(url('/admin/equipos')) ?>" class="btn btn-claro">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="panel-cuerpo">
        <?php if (!$equipos): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">No hay equipos con esos filtros</p>
                <p class="estado-vacio-texto">Cambia los filtros o presiona "Limpiar".</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Nombre del equipo</th>
                        <th scope="col" class="d-none d-md-table-cell">Categoría</th>
                        <th scope="col" class="text-end d-none d-lg-table-cell">Tarifa diaria</th>
                        <th scope="col" class="d-none d-lg-table-cell">Fecha de compra</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="col-acciones">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($equipos as $equipo): ?>
                        <tr>
                            <td><span class="codigo"><?= e($equipo['codigoInventario']) ?></span></td>
                            <td>
                                <span class="texto-fuerte"><?= e($equipo['nombre']) ?></span>
                                <?php if ($equipo['numeroSerie'] !== null): ?>
                                    <br><span class="texto-suave small">Serie <?= e($equipo['numeroSerie']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell"><?= e($equipo['categoria']) ?></td>
                            <td class="text-end d-none d-lg-table-cell"><?= dinero($equipo['precioDiario']) ?></td>
                            <td class="d-none d-lg-table-cell"><?= e(fechaCorta($equipo['fechaAdquisicion'])) ?></td>
                            <td>
                                <?= estadoBadge($equipo['estado']) ?>
                                <?php if (!$equipo['activo']): ?>
                                    <?= estadoBadge('Inactivo') ?>
                                <?php endif; ?>
                            </td>
                            <td class="col-acciones">
                                <a href="<?= e(url('/admin/equipos/detalle?id=' . $equipo['idEquipo'])) ?>" class="btn btn-secondary btn-sm"
                                   title="Ver detalle" aria-label="Ver detalle de <?= e($equipo['codigoInventario']) ?>">
                                    <i class="bi bi-eye-fill" aria-hidden="true"></i>
                                </a>
                                <button type="button" class="btn btn-secondary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#modalEquipo"
                                        data-accion="<?= e(url('/admin/equipos/actualizar')) ?>"
                                        data-titulo="Editar equipo" data-modo="editar"
                                        data-campos="<?= e(json_encode(Equipo::camposFormulario($equipo))) ?>"
                                        title="Editar" aria-label="Editar <?= e($equipo['codigoInventario']) ?>">
                                    <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pie-tabla">
                <p class="pie-tabla-texto">
                    Mostrando <?= e($desplazamiento + 1) ?>–<?= e($desplazamiento + count($equipos)) ?>
                    de <?= e($total) ?> <?= $total === 1 ? 'equipo' : 'equipos' ?>
                </p>
                <?php
                $rutaPaginacion = '/admin/equipos';
                $parametrosPaginacion = $filtros;
                require RAIZ . '/app/views/componentes/paginacion.php';
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$origen = 'listado';
require RAIZ . '/app/views/equipos/_formulario.php';
?>
