<?php
/**
 * Tarjeta de un equipo del catálogo (Figma "Catalogo - Daniel"). Se usa en el
 * catálogo, en Disponibilidad y en los destacados del Inicio.
 *
 * Antes de incluirla, la vista define:
 *   $equipo  fila con idEquipo, nombre, categoria, precioDiario, fotoPrincipal
 *            (y, en Disponibilidad, dias y subtotalEstimado)
 *   $accion  'ver'     -> botón "Solicitar alquiler" que lleva al detalle del equipo
 *            'agregar' -> formulario POST "Agregar a mi solicitud" con $inicio y $fin
 */
$urlEquipo = url('/cliente/equipo?id=' . $equipo['idEquipo']);
?>
<article class="tarjeta-equipo">
    <a href="<?= e($urlEquipo) ?>" class="tarjeta-equipo-imagen" tabindex="-1" aria-hidden="true">
        <?= imagenEquipo($equipo['fotoPrincipal'], $equipo['nombre'], iconoCategoria($equipo['categoria'])) ?>
    </a>
    <p class="tarjeta-equipo-etiqueta"><?= e($equipo['categoria']) ?></p>
    <h3 class="tarjeta-equipo-nombre"><a href="<?= e($urlEquipo) ?>"><?= e($equipo['nombre']) ?></a></h3>
    <p class="tarjeta-equipo-precio"><?= dinero($equipo['precioDiario']) ?> <span>/día</span></p>
    <?php if (isset($equipo['subtotalEstimado'])): ?>
        <p class="tarjeta-equipo-subtotal"><?= e($equipo['dias']) ?> días · <?= dinero($equipo['subtotalEstimado']) ?></p>
    <?php endif; ?>

    <div class="tarjeta-equipo-acciones">
        <?php if ($accion === 'agregar'): ?>
            <form method="post" action="<?= e(url('/cliente/solicitud/agregar')) ?>" class="w-100">
                <?= csrfCampo() ?>
                <input type="hidden" name="idEquipo" value="<?= e($equipo['idEquipo']) ?>">
                <input type="hidden" name="inicio" value="<?= e($inicio) ?>">
                <input type="hidden" name="fin" value="<?= e($fin) ?>">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-cart-plus" aria-hidden="true"></i> Agregar a mi solicitud</button>
            </form>
        <?php else: ?>
            <a href="<?= e($urlEquipo) ?>" class="btn btn-primary btn-sm w-100">Solicitar alquiler</a>
        <?php endif; ?>
    </div>
</article>
