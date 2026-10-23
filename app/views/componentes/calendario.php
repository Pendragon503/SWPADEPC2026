<?php
/**
 * Calendario de un mes con los días ocupados (Figma "Disponibilidad - Daniel").
 * Antes de incluirlo, la vista define:
 *   $calendario  resultado de calendarioMes()
 *   $urlMes      URL base para cambiar de mes; se le agrega "&mes=AAAA-MM"
 */
$diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$textosEstado = ['fuera' => '', 'pasado' => 'ya pasó', 'ocupado' => 'ocupado', 'libre' => 'libre'];
?>
<div class="calendario">
    <div class="calendario-cabecera">
        <?php if ($calendario['anterior'] !== null): ?>
            <a href="<?= e($urlMes . '&mes=' . $calendario['anterior']) ?>#calendario" class="btn btn-claro btn-sm" aria-label="Mes anterior">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </a>
        <?php else: ?>
            <span class="btn btn-claro btn-sm disabled" aria-hidden="true"><i class="bi bi-chevron-left"></i></span>
        <?php endif; ?>
        <h3 class="calendario-titulo"><?= e($calendario['titulo']) ?></h3>
        <a href="<?= e($urlMes . '&mes=' . $calendario['siguiente']) ?>#calendario" class="btn btn-claro btn-sm" aria-label="Mes siguiente">
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </a>
    </div>
    <table class="calendario-tabla">
        <thead>
        <tr>
            <?php foreach ($diasSemana as $nombreDia): ?>
                <th scope="col"><?= e($nombreDia) ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($calendario['semanas'] as $semana): ?>
            <tr>
                <?php foreach ($semana as $dia): ?>
                    <?php $texto = $textosEstado[$dia['estado']]; ?>
                    <td>
                        <span class="calendario-dia calendario-<?= e($dia['estado']) ?>" title="<?= e(fechaCorta($dia['fecha']) . ($texto ? ": $texto" : '')) ?>">
                            <?= e($dia['numero']) ?>
                            <?php if ($texto !== ''): ?><span class="visually-hidden"><?= e($texto) ?></span><?php endif; ?>
                        </span>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <ul class="calendario-leyenda">
        <li><span class="calendario-dia calendario-libre" aria-hidden="true"></span> Libre</li>
        <li><span class="calendario-dia calendario-ocupado" aria-hidden="true"></span> Ocupado</li>
        <li><span class="calendario-dia calendario-pasado" aria-hidden="true"></span> Ya pasó</li>
    </ul>
</div>
