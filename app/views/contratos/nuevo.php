<?php
/**
 * Nuevo alquiler, paso 1 de 2 (RF-05, RF-06): fechas y equipos libres en ese período.
 * La lista sale de sp_equiposDisponibles, igual que la disponibilidad del cliente.
 *
 * @var array      $solicitud    ['inicio', 'fin', 'equipos' => ids marcados]
 * @var int        $idCategoria
 * @var array      $categorias   Catalogo::categoriasConTotal()
 * @var array      $errores      de las fechas (['inicio' | 'fin' => 'mensaje'])
 * @var array|null $equipos      Catalogo::disponibles(); null si todavía no se eligieron fechas
 */
?>
<a href="<?= e(url('/contratos')) ?>" class="enlace-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a contratos</a>

<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo">Nuevo alquiler</h2>
        <p class="encabezado-pagina-texto">Paso 1 de 2: elige las fechas y marca los equipos. El contrato queda Aprobado al registrarlo.</p>
    </div>
</div>

<section class="panel mb-4">
    <div class="panel-cabecera panel-cabecera-linea">
        <h3 class="panel-titulo panel-titulo-mayus"><i class="bi bi-calendar-range" aria-hidden="true"></i> Fechas del alquiler</h3>
    </div>
    <div class="panel-cuerpo">
        <form method="get" action="<?= e(url('/contratos/nuevo')) ?>" class="filtros-contratos" novalidate>
            <div>
                <label for="inicio" class="form-label">Fecha de inicio</label>
                <input type="date" id="inicio" name="inicio" min="<?= e(date('Y-m-d')) ?>" required
                       class="form-control<?= isset($errores['inicio']) ? ' is-invalid' : '' ?>" value="<?= e($solicitud['inicio']) ?>">
                <?php if (isset($errores['inicio'])): ?><div class="invalid-feedback"><?= e($errores['inicio']) ?></div><?php endif; ?>
            </div>
            <div>
                <label for="fin" class="form-label">Fecha de devolución</label>
                <input type="date" id="fin" name="fin" min="<?= e(date('Y-m-d')) ?>" required
                       class="form-control<?= isset($errores['fin']) ? ' is-invalid' : '' ?>" value="<?= e($solicitud['fin']) ?>">
                <?php if (isset($errores['fin'])): ?><div class="invalid-feedback"><?= e($errores['fin']) ?></div><?php endif; ?>
            </div>
            <div>
                <label for="idCategoria" class="form-label">Categoría</label>
                <select id="idCategoria" name="idCategoria" class="form-select">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= e($categoria['idCategoria']) ?>"<?= (int) $categoria['idCategoria'] === $idCategoria ? ' selected' : '' ?>><?= e($categoria['categoria']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtros-tabla-botones">
                <button type="submit" class="btn btn-secondary"><i class="bi bi-search" aria-hidden="true"></i> Ver equipos libres</button>
            </div>
        </form>
    </div>
</section>

<?php if ($equipos !== null): ?>
    <section class="panel">
        <div class="panel-cabecera panel-cabecera-linea">
            <h3 class="panel-titulo panel-titulo-mayus"><i class="bi bi-box-seam" aria-hidden="true"></i> Equipos libres del <?= e(periodo($solicitud['inicio'], $solicitud['fin'])) ?></h3>
        </div>
        <div class="panel-cuerpo">
            <?php if (!$equipos): ?>
                <div class="estado-vacio">
                    <span class="estado-vacio-icono"><i class="bi bi-calendar-x" aria-hidden="true"></i></span>
                    <p class="estado-vacio-titulo">No hay equipos libres en esas fechas</p>
                    <p class="estado-vacio-texto">Prueba con otras fechas u otra categoría.</p>
                </div>
            <?php else: ?>
                <?php // GET: revisar no cambia nada; solo calcula el importe de los equipos marcados. ?>
                <form method="get" action="<?= e(url('/contratos/nuevo/revisar')) ?>">
                    <input type="hidden" name="inicio" value="<?= e($solicitud['inicio']) ?>">
                    <input type="hidden" name="fin" value="<?= e($solicitud['fin']) ?>">
                    <div class="table-responsive">
                        <table class="table tabla-er">
                            <thead>
                            <tr>
                                <th scope="col"><span class="visually-hidden">Elegir</span></th>
                                <th scope="col">Equipo</th>
                                <th scope="col" class="d-none d-md-table-cell">Categoría</th>
                                <th scope="col" class="text-end">Tarifa diaria</th>
                                <th scope="col" class="text-end d-none d-sm-table-cell">Días</th>
                                <th scope="col" class="text-end">Subtotal</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($equipos as $equipo): ?>
                                <?php $idCasilla = 'equipo-' . (int) $equipo['idEquipo']; ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input" id="<?= e($idCasilla) ?>" name="equipos[]" value="<?= e($equipo['idEquipo']) ?>"
                                            <?= in_array((int) $equipo['idEquipo'], $solicitud['equipos'], true) ? ' checked' : '' ?>>
                                    </td>
                                    <td>
                                        <label for="<?= e($idCasilla) ?>" class="texto-fuerte"><?= e($equipo['nombre']) ?></label>
                                        <br><span class="codigo"><?= e($equipo['codigoInventario']) ?></span>
                                    </td>
                                    <td class="d-none d-md-table-cell"><?= e($equipo['categoria']) ?></td>
                                    <td class="text-end"><?= dinero($equipo['precioDiario']) ?></td>
                                    <td class="text-end d-none d-sm-table-cell"><?= e($equipo['dias']) ?></td>
                                    <td class="text-end texto-fuerte"><?= dinero($equipo['subtotalEstimado']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pie-tabla">
                        <p class="pie-tabla-texto">Marca de 1 a <?= Solicitud::MAXIMO_EQUIPOS ?> equipos. En el siguiente paso eliges el cliente y ves el importe.</p>
                        <button type="submit" class="btn btn-primary">Revisar importe <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
