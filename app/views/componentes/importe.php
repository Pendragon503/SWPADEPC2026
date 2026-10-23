<?php
/**
 * Tabla de importe (RF-07): por equipo la tarifa diaria, los días y el subtotal; al
 * pie el subtotal, el depósito de garantía y el total. Se usa en "Mi solicitud" y
 * en el detalle de un alquiler.
 *
 * Antes de incluirla, la vista define:
 *   $importe      ['lineas' => [[nombre, codigoInventario, precioDiario, dias, subtotal, idEquipo], ...],
 *                  'subtotal', 'deposito', 'total']
 *   $puedeQuitar  true para mostrar el botón "Quitar" en cada línea ("Mi solicitud")
 */
?>
<div class="table-responsive">
    <table class="table tabla-er tabla-importe">
        <thead>
        <tr>
            <th scope="col">Equipo</th>
            <th scope="col" class="text-end">Tarifa diaria</th>
            <th scope="col" class="text-end">Días</th>
            <th scope="col" class="text-end">Subtotal</th>
            <?php if ($puedeQuitar): ?><th scope="col" class="col-acciones"><span class="visually-hidden">Acciones</span></th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($importe['lineas'] as $linea): ?>
            <tr>
                <td>
                    <span class="texto-fuerte"><?= e($linea['nombre']) ?></span>
                    <br><span class="codigo"><?= e($linea['codigoInventario']) ?></span>
                </td>
                <td class="text-end"><?= dinero($linea['precioDiario']) ?></td>
                <td class="text-end"><?= e($linea['dias']) ?></td>
                <td class="text-end texto-fuerte"><?= dinero($linea['subtotal']) ?></td>
                <?php if ($puedeQuitar): ?>
                    <td class="col-acciones">
                        <form method="post" action="<?= e(url('/cliente/solicitud/quitar')) ?>">
                            <?= csrfCampo() ?>
                            <input type="hidden" name="idEquipo" value="<?= e($linea['idEquipo']) ?>">
                            <button type="submit" class="btn btn-claro btn-sm" aria-label="Quitar <?= e($linea['nombre']) ?>">
                                <i class="bi bi-x-lg" aria-hidden="true"></i> Quitar
                            </button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr><th scope="row" colspan="3" class="text-end">Subtotal</th><td class="text-end"><?= dinero($importe['subtotal']) ?></td><?php if ($puedeQuitar): ?><td></td><?php endif; ?></tr>
        <tr><th scope="row" colspan="3" class="text-end">Depósito de garantía</th><td class="text-end"><?= dinero($importe['deposito']) ?></td><?php if ($puedeQuitar): ?><td></td><?php endif; ?></tr>
        <tr class="tabla-importe-total"><th scope="row" colspan="3" class="text-end">Total</th><td class="text-end"><?= dinero($importe['total']) ?></td><?php if ($puedeQuitar): ?><td></td><?php endif; ?></tr>
        </tfoot>
    </table>
</div>
