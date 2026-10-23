<?php
/**
 * Disponibilidad por fechas (RF-05): el cliente elige el rango y ve los equipos libres.
 * Sin frame propio para la lista: sigue el estilo del Inicio y del Catálogo.
 *
 * @var array $consulta    ['inicio', 'fin', 'idCategoria']
 * @var array $errores     errores de las fechas (Solicitud::validarFechas)
 * @var bool  $buscado     true si hay fechas válidas y se consultó
 * @var array $equipos     Catalogo::disponibles() (sp_equiposDisponibles)
 * @var array $categorias  Catalogo::categoriasConTotal()
 */
?>
<div class="contenedor-cliente contenido-cliente">
    <div class="encabezado-pagina">
        <div>
            <h1 class="encabezado-pagina-titulo">Consulta de disponibilidad</h1>
            <p class="encabezado-pagina-texto">Elige las fechas de tu obra y te mostramos los equipos libres en todo ese período.</p>
        </div>
    </div>

    <section class="panel mb-4">
        <div class="panel-cuerpo">
            <form method="get" action="<?= e(url('/cliente/disponibilidad')) ?>" class="consulta-fechas" novalidate>
                <div>
                    <label for="inicio" class="form-label">Desde</label>
                    <input type="date" id="inicio" name="inicio" min="<?= e(date('Y-m-d')) ?>" required
                           class="form-control<?= isset($errores['inicio']) ? ' is-invalid' : '' ?>" value="<?= e($consulta['inicio']) ?>">
                    <?php if (isset($errores['inicio'])): ?><div class="invalid-feedback"><?= e($errores['inicio']) ?></div><?php endif; ?>
                </div>
                <div>
                    <label for="fin" class="form-label">Hasta</label>
                    <input type="date" id="fin" name="fin" min="<?= e(date('Y-m-d')) ?>" required
                           class="form-control<?= isset($errores['fin']) ? ' is-invalid' : '' ?>" value="<?= e($consulta['fin']) ?>">
                    <?php if (isset($errores['fin'])): ?><div class="invalid-feedback"><?= e($errores['fin']) ?></div><?php endif; ?>
                </div>
                <div>
                    <label for="idCategoria" class="form-label">Categoría</label>
                    <select id="idCategoria" name="idCategoria" class="form-select">
                        <option value="0">Todas</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= e($categoria['idCategoria']) ?>"<?= (int) $categoria['idCategoria'] === $consulta['idCategoria'] ? ' selected' : '' ?>>
                                <?= e($categoria['categoria']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Ver disponibles</button>
            </form>
        </div>
    </section>

    <?php if ($buscado): ?>
        <h2 class="titulo-seccion">
            <?= count($equipos) ?> <?= count($equipos) === 1 ? 'equipo libre' : 'equipos libres' ?>
            del <?= e(periodo($consulta['inicio'], $consulta['fin'])) ?>
        </h2>
        <?php if (!$equipos): ?>
            <section class="panel">
                <div class="estado-vacio">
                    <span class="estado-vacio-icono"><i class="bi bi-calendar-x" aria-hidden="true"></i></span>
                    <p class="estado-vacio-titulo">No hay equipos libres en esas fechas</p>
                    <p class="estado-vacio-texto">Prueba con otro período o con otra categoría.</p>
                </div>
            </section>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($equipos as $equipo): ?>
                    <div class="col-sm-6 col-lg-4 col-xl-3">
                        <?php $accion = 'ver'; require RAIZ . '/app/views/componentes/tarjetaEquipo.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
