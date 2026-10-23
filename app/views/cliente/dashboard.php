<?php
/**
 * Inicio del Cliente (Figma "Inicio - Daniel"): portada con buscador (lleva al
 * catálogo), categorías populares, equipos destacados y sus últimos alquileres.
 *
 * @var array $categorias  Catalogo::categoriasConTotal() (para la lista del buscador)
 * @var array $populares   las 4 categorías con más equipos
 * @var array $destacados  Catalogo::equiposDestacados()
 * @var array $alquileres  los 3 contratos más recientes del cliente
 */
?>
<section class="portada-cliente">
    <div class="contenedor-cliente">
        <p class="portada-saludo">Hola, <?= e(Auth::usuario()['nombres']) ?></p>
        <h1 class="portada-titulo">El equipo que necesitas, <span>cuando lo necesitas.</span></h1>
        <p class="portada-texto">
            Alquila maquinaria y equipos de alta calidad para tus proyectos de construcción
            de forma fácil, rápida y segura.
        </p>

        <?php // Búsqueda con GET al catálogo: los mismos nombres de campo que su filtro. ?>
        <form class="buscador-portada" role="search" method="get" action="<?= e(url('/cliente/catalogo')) ?>">
            <div class="buscador-portada-campos">
                <div class="buscador-portada-texto">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label for="buscarEquipo" class="visually-hidden">Buscar equipos</label>
                    <input type="search" id="buscarEquipo" name="buscar" class="form-control" placeholder="Buscar equipos, maquinaria...">
                </div>
                <label for="buscarCategoria" class="visually-hidden">Categoría</label>
                <select id="buscarCategoria" name="idCategoria" class="form-select">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= e($categoria['idCategoria']) ?>"><?= e($categoria['categoria']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </form>
        <p class="portada-nota">
            ¿Ya tienes fechas? <a href="<?= e(url('/cliente/disponibilidad')) ?>" class="link-light">Busca los equipos libres en tu período</a>.
        </p>
    </div>
</section>

<div class="contenedor-cliente contenido-cliente">
    <div class="row g-4">
        <div class="col-lg-9">
            <section class="mb-4">
                <h2 class="titulo-seccion">Categorías populares</h2>
                <div class="row g-3">
                    <?php foreach ($populares as $categoria): ?>
                        <div class="col-6 col-md-3">
                            <a href="<?= e(url('/cliente/catalogo?idCategoria=' . $categoria['idCategoria'])) ?>" class="tarjeta-categoria">
                                <span class="tarjeta-categoria-icono">
                                    <i class="bi <?= e(iconoCategoria($categoria['categoria'])) ?>" aria-hidden="true"></i>
                                </span>
                                <span class="tarjeta-categoria-nombre"><?= e($categoria['categoria']) ?></span>
                                <span class="tarjeta-categoria-total"><?= e($categoria['total']) ?> <?= (int) $categoria['total'] === 1 ? 'equipo' : 'equipos' ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section>
                <h2 class="titulo-seccion">Equipos destacados</h2>
                <div class="row g-3">
                    <?php $accion = 'ver'; foreach ($destacados as $equipo): ?>
                        <div class="col-sm-6 col-md-3">
                            <?php require RAIZ . '/app/views/componentes/tarjetaEquipo.php'; ?>
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
                        <?php if ($alquileres): ?>
                            <a href="<?= e(url('/cliente/alquileres')) ?>" class="small">Ver todos</a>
                        <?php endif; ?>
                    </div>
                    <div class="panel-cuerpo">
                        <?php if (!$alquileres): ?>
                            <div class="estado-vacio">
                                <span class="estado-vacio-icono"><i class="bi bi-clipboard" aria-hidden="true"></i></span>
                                <p class="estado-vacio-titulo">Aún no tienes alquileres</p>
                                <p class="estado-vacio-texto">Cuando envíes una solicitud aparecerá aquí.</p>
                            </div>
                        <?php else: ?>
                            <ul class="lista-alquileres">
                                <?php foreach ($alquileres as $alquiler): ?>
                                    <li>
                                        <a href="<?= e(url('/cliente/alquiler?id=' . $alquiler['idContrato'])) ?>" class="lista-alquileres-enlace">
                                            <span class="lista-alquileres-nombre">
                                                <?= e($alquiler['primerEquipo']) ?><?= $alquiler['equipos'] > 1 ? ' +' . e($alquiler['equipos'] - 1) : '' ?>
                                            </span>
                                            <span class="lista-alquileres-fechas"><?= e(periodo($alquiler['fechaInicio'], $alquiler['fechaFin'])) ?></span>
                                            <?= estadoBadge($alquiler['estado']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
