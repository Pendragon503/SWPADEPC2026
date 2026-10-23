<?php
/**
 * Catálogo de equipos (Figma "Catalogo - Daniel"): filtros a la izquierda y tarjetas a la derecha.
 *
 * @var array $equipos      Catalogo::listar()
 * @var array $categorias   Catalogo::categoriasConTotal()
 * @var array $filtros      ['buscar', 'idCategoria']
 * @var int   $total, $pagina, $totalPaginas, $desplazamiento
 */
?>
<div class="contenedor-cliente contenido-cliente">
    <div class="encabezado-pagina">
        <div>
            <h1 class="encabezado-pagina-titulo">Catálogo de equipos</h1>
            <p class="encabezado-pagina-texto">Filtra y elige los equipos que necesitas para tu obra.</p>
        </div>
        <a href="<?= e(url('/cliente/disponibilidad')) ?>" class="btn btn-secondary"><i class="bi bi-calendar-check" aria-hidden="true"></i> Buscar por fechas</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <?php // Filtros con GET: quedan en la URL. Una sola categoría a la vez (radio). ?>
            <form method="get" action="<?= e(url('/cliente/catalogo')) ?>" class="panel filtro-catalogo" role="search">
                <div class="panel-cabecera panel-cabecera-linea">
                    <h2 class="panel-titulo panel-titulo-mayus">Filtrar</h2>
                </div>
                <div class="panel-cuerpo">
                    <div class="buscador-tabla-campo mb-3">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <label for="buscar" class="visually-hidden">Buscar equipos</label>
                        <input type="search" id="buscar" name="buscar" class="form-control"
                               placeholder="Nombre, marca o modelo..." value="<?= e($filtros['buscar']) ?>">
                    </div>
                    <fieldset>
                        <legend class="form-label">Categorías</legend>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="idCategoria" id="categoria0" value="0"<?= $filtros['idCategoria'] === 0 ? ' checked' : '' ?>>
                            <label class="form-check-label" for="categoria0">Todas (<?= e($totalAlquilables) ?>)</label>
                        </div>
                        <?php foreach ($categorias as $categoria): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="idCategoria" id="categoria<?= e($categoria['idCategoria']) ?>"
                                       value="<?= e($categoria['idCategoria']) ?>"<?= (int) $categoria['idCategoria'] === $filtros['idCategoria'] ? ' checked' : '' ?>>
                                <label class="form-check-label" for="categoria<?= e($categoria['idCategoria']) ?>">
                                    <?= e($categoria['categoria']) ?> (<?= e($categoria['total']) ?>)
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                    <button type="submit" class="btn btn-oscuro w-100 mt-3">Aplicar filtros</button>
                    <?php if ($filtros['buscar'] !== '' || $filtros['idCategoria'] > 0): ?>
                        <a href="<?= e(url('/cliente/catalogo')) ?>" class="btn btn-claro w-100 mt-2">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="col-lg-9">
            <?php if (!$equipos): ?>
                <section class="panel">
                    <div class="estado-vacio">
                        <span class="estado-vacio-icono"><i class="bi bi-search" aria-hidden="true"></i></span>
                        <p class="estado-vacio-titulo">No hay equipos con esos filtros</p>
                        <p class="estado-vacio-texto">Prueba con otra palabra o elige "Todas" las categorías.</p>
                    </div>
                </section>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($equipos as $equipo): ?>
                        <div class="col-sm-6 col-xl-4">
                            <?php $accion = 'ver'; require RAIZ . '/app/views/componentes/tarjetaEquipo.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="pie-tabla">
                    <p class="pie-tabla-texto">
                        Mostrando <?= e($desplazamiento + 1) ?>–<?= e($desplazamiento + count($equipos)) ?> de <?= e($total) ?> equipos
                    </p>
                    <?php
                    $rutaPaginacion = '/cliente/catalogo';
                    $parametrosPaginacion = ['buscar' => $filtros['buscar'], 'idCategoria' => $filtros['idCategoria'] ?: ''];
                    require RAIZ . '/app/views/componentes/paginacion.php';
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
