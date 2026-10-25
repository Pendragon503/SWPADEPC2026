<?php
/**
 * Campos de un equipo al entregarlo o recibirlo: estado físico, combustible, horómetro,
 * accesorios y (al recibir) observaciones. Los nombres son equipos[id][campo].
 *
 * Antes de incluirlo, la vista define:
 *   $linea             la línea del contrato (Contrato::lineas())
 *   $campos            lo escrito en el último envío para este equipo ([] si no hay)
 *   $errores           errores del formulario ("equipos[5][lecturaHorometro]" => 'mensaje')
 *   $conObservaciones  true en la devolución
 */
$id = (int) $linea['idDetalleContrato'];
$nombre = function (string $campo) use ($id): string {
    return "equipos[$id][$campo]";
};
$clase = function (string $campo) use ($nombre, $errores): string {
    return isset($errores[$nombre($campo)]) ? ' is-invalid' : '';
};
$error = function (string $campo) use ($nombre, $errores): string {
    return isset($errores[$nombre($campo)]) ? '<div class="invalid-feedback">' . e($errores[$nombre($campo)]) . '</div>' : '';
};
?>
<div class="row g-2">
    <div class="col-sm-6 col-lg-3">
        <label class="form-label" for="estado-<?= $id ?>">Estado físico *</label>
        <select id="estado-<?= $id ?>" name="<?= e($nombre('estadoFisico')) ?>" class="form-select<?= $clase('estadoFisico') ?>">
            <option value="">Elige…</option>
            <?php foreach (Entrega::ESTADOS_FISICOS as $estado): ?>
                <option<?= ($campos['estadoFisico'] ?? '') === $estado ? ' selected' : '' ?>><?= e($estado) ?></option>
            <?php endforeach; ?>
        </select>
        <?= $error('estadoFisico') ?>
    </div>
    <div class="col-6 col-lg-2">
        <label class="form-label" for="combustible-<?= $id ?>">Combustible %</label>
        <input type="number" id="combustible-<?= $id ?>" name="<?= e($nombre('nivelCombustible')) ?>" min="0" max="100" step="1"
               class="form-control<?= $clase('nivelCombustible') ?>" value="<?= e($campos['nivelCombustible'] ?? '') ?>">
        <?= $error('nivelCombustible') ?>
    </div>
    <div class="col-6 col-lg-2">
        <label class="form-label" for="horometro-<?= $id ?>">Horómetro (h)</label>
        <input type="number" id="horometro-<?= $id ?>" name="<?= e($nombre('lecturaHorometro')) ?>" min="0" step="0.1"
               class="form-control<?= $clase('lecturaHorometro') ?>" value="<?= e($campos['lecturaHorometro'] ?? '') ?>"
               <?= $linea['horometroSalida'] !== null && $conObservaciones ? 'placeholder="Salida: ' . e($linea['horometroSalida']) . '"' : '' ?>>
        <?= $error('lecturaHorometro') ?>
    </div>
    <div class="col-lg-5">
        <label class="form-label" for="accesorios-<?= $id ?>">Accesorios</label>
        <input type="text" id="accesorios-<?= $id ?>" name="<?= e($nombre('accesorios')) ?>" maxlength="300"
               class="form-control<?= $clase('accesorios') ?>" placeholder="Ej. Balde, llave de encendido, extintor" value="<?= e($campos['accesorios'] ?? '') ?>">
        <?= $error('accesorios') ?>
    </div>
    <?php if ($conObservaciones): ?>
        <div class="col-12">
            <label class="form-label" for="observaciones-<?= $id ?>">Observaciones de recepción</label>
            <input type="text" id="observaciones-<?= $id ?>" name="<?= e($nombre('observaciones')) ?>" maxlength="500"
                   class="form-control<?= $clase('observaciones') ?>" placeholder="Ej. Prueba de encendido correcta" value="<?= e($campos['observaciones'] ?? '') ?>">
            <?= $error('observaciones') ?>
        </div>
    <?php endif; ?>
</div>
