<?php
/**
 * Inicio del Cliente: portada con buscador, categorías populares, equipos
 * destacados (datos reales de v_catalogoEquipo) y "Mis alquileres".
 * El buscador y los botones se activan con el módulo de Catálogo.
 *
 * @var array $categorias
 * @var array $destacados
 * @var array $opcionesCategoria
 */

// Icono por categoría; las que no estén aquí usan bi-tools.
$iconosCategoria = [
    'Minicargadores'        => 'bi-truck-front-fill',
    'Excavadoras'           => 'bi-cone-striped',
    'Martillos Hidráulicos' => 'bi-hammer',
    'Compactadores'         => 'bi-layers-fill',
    'Generadores Diésel'    => 'bi-lightning-charge-fill',
    'Retroexcavadoras'      => 'bi-truck-flatbed',
];
?>
<section class="portada-cliente">
    <div class="contenedor-cliente">
        <p class="portada-saludo">Hola, <?= e(Auth::usuario()['nombres']) ?></p>
        <h1 class="portada-titulo">El equipo que necesitas, <span>cuando lo necesitas.</span></h1>
        <p class="portada-texto">
            Alquila maquinaria y equipos de alta calidad para tus proyectos de construcción
            de forma fácil, rápida y segura.
        </p>

        <form class="buscador-portada" role="search" action="#">
            <fieldset disabled class="buscador-portada-campos">
                <div class="buscador-portada-texto">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label for="buscarEquipo" class="visually-hidden">Buscar equipos</label>
                    <input type="search" id="buscarEquipo" class="form-control" placeholder="Buscar equipos, maquinaria...">
                </div>
                <label for="buscarCategoria" class="visually-hidden">Categoría</label>
                <select id="buscarCategoria" class="form-select">
                    <option>Todas las categorías</option>
                    <?php foreach ($opcionesCategoria as $opcion): ?>
                        <option value="<?= e($opcion['idCategoria']) ?>"><?= e($opcion['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Buscar</button>
            </fieldset>
        </form>
        <p class="portada-nota"><i class="bi bi-info-circle" aria-hidden="true"></i> La búsqueda estará disponible con el módulo de Catálogo.</p>
    </div>
</section>

<div class="contenedor-cliente contenido-cliente">
    <div class="row g-4">
        <div class="col-lg-9">
            <section class="mb-4">
                <h2 class="titulo-seccion">Categorías populares</h2>
                <div class="row g-3">
                    <?php foreach ($categorias as $categoria): ?>
                        <div class="col-6 col-md-3">
                            <a href="#" class="tarjeta-categoria">
                                <span class="tarjeta-categoria-icono">
                                    <i class="bi <?= e($iconosCategoria[$categoria['nombre']] ?? 'bi-tools') ?>" aria-hidden="true"></i>
                                </span>
                                <span class="tarjeta-categoria-nombre"><?= e($categoria['nombre']) ?></span>
                                <span class="tarjeta-categoria-total"><?= e($categoria['equipos']) ?> <?= (int) $categoria['equipos'] === 1 ? 'equipo' : 'equipos' ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section>
                <h2 class="titulo-seccion">Equipos destacados</h2>
                <div class="row g-3">
                    <?php foreach ($destacados as $equipo): ?>
                        <div class="col-sm-6 col-md-3">
                            <article class="tarjeta-equipo">
                                <div class="tarjeta-equipo-imagen">
                                    <?php // Foto principal, o el marcador gris con el icono de la categoría si no hay archivo. ?>
                                    <?= imagenEquipo($equipo['fotoPrincipal'], $equipo['nombre'], $iconosCategoria[$equipo['categoria']] ?? 'bi-tools') ?>
                                </div>
                                <h3 class="tarjeta-equipo-nombre"><?= e($equipo['nombre']) ?></h3>
                                <p class="tarjeta-equipo-categoria"><?= e($equipo['categoria']) ?></p>
                                <p class="tarjeta-equipo-precio">
                                    $<?= e(number_format((float) $equipo['precioDiario'], 2)) ?> <span>/día</span>
                                </p>
                                <div class="tarjeta-equipo-acciones">
                                    <a href="#" class="btn btn-claro btn-sm">Ver detalle</a>
                                    <a href="#" class="btn btn-calendario btn-sm" title="Ver disponibilidad" aria-label="Ver disponibilidad de <?= e($equipo['nombre']) ?>">
                                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <div class="col-lg-3">
            <aside>
            <section class="panel">
                <div class="panel-cabecera panel-cabecera-linea">
                    <h2 class="panel-titulo panel-titulo-mayus">Mis alquileres</h2>
                </div>
                <div class="panel-cuerpo">
                    <div class="estado-vacio">
                        <span class="estado-vacio-icono"><i class="bi bi-clipboard" aria-hidden="true"></i></span>
                        <p class="estado-vacio-titulo">Aún no hay alquileres para mostrar</p>
                        <p class="estado-vacio-texto">Se conectará en el módulo "Mis alquileres".</p>
                    </div>
                </div>
            </section>
            </aside>
        </div>
    </div>
</div>
