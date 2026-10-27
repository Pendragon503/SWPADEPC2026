<?php
/**
 * Configuración → Desactivación de equipos: los equipos dados de baja, con su última baja
 * de la bitácora, y "Reactivar" (mismas reglas que en el detalle del equipo).
 *
 * @var array $inactivos  Equipo::inactivos()
 */
$pestana = 'desactivacion';
require RAIZ . '/app/views/configuracion/_encabezado.php';
?>
<section class="panel">
    <div class="panel-cabecera panel-cabecera-linea">
        <h3 class="panel-titulo panel-titulo-mayus">Equipos dados de baja</h3>
    </div>
    <div class="panel-cuerpo">
        <?php if (!$inactivos): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">No hay equipos dados de baja</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Equipo</th>
                        <th scope="col" class="d-none d-md-table-cell">Categoría</th>
                        <th scope="col">Fecha de baja</th>
                        <th scope="col">Motivo</th>
                        <th scope="col" class="d-none d-lg-table-cell">Realizada por</th>
                        <th scope="col" class="col-acciones">Acción</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($inactivos as $equipo): ?>
                        <tr>
                            <td><a class="codigo" href="<?= e(url('/admin/equipos/detalle?id=' . $equipo['idEquipo'])) ?>"><?= e($equipo['codigoInventario']) ?></a></td>
                            <td class="texto-fuerte"><?= e($equipo['nombre']) ?></td>
                            <td class="d-none d-md-table-cell"><?= e($equipo['categoria']) ?><?= $equipo['categoriaActiva'] ? '' : ' <span class="texto-suave">(inactiva)</span>' ?></td>
                            <td class="text-nowrap"><?= e(fechaCorta($equipo['fechaBaja'], true)) ?></td>
                            <td><?= e($equipo['motivo'] ?? '—') ?></td>
                            <td class="d-none d-lg-table-cell"><?= e($equipo['usuario'] ?? '—') ?></td>
                            <td class="col-acciones">
                                <form method="post" action="<?= e(url('/admin/equipos/cambiar-activo')) ?>"
                                      data-confirmar="¿Reactivar <?= e($equipo['codigoInventario']) ?>? Volverá a estar Disponible.">
                                    <?= csrfCampo() ?>
                                    <input type="hidden" name="idEquipo" value="<?= e($equipo['idEquipo']) ?>">
                                    <input type="hidden" name="activar" value="1">
                                    <input type="hidden" name="volver" value="configuracion">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reactivar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
