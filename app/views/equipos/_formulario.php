<?php
/**
 * Modal de registrar / editar equipo (.modal-er, Figma "Registrar equipo"). Se incluye
 * desde equipos/index.php y equipos/detalle.php.
 *
 * A la vista: ID, nombre, categoría y cantidad (al registrar) y la fecha de compra. El resto
 * va en la "Ficha técnica (opcional)", un <details> que se abre solo si hay un error en ella.
 * Con Cantidad > 1 se registran unidades con códigos consecutivos (Equipo::crear).
 *
 * Antes de incluirlo, la vista define:
 *   $formulario  datos del último envío con errores, o null (los deja EquipoController)
 *   $categorias  todas las categorías; las inactivas se marcan "(inactiva)" y el modelo
 *                solo acepta una inactiva si es la que el equipo ya tenía
 *   $origen      'listado' o 'detalle': a dónde vuelve el formulario después de guardar
 *   $volver      filtros del listado (query string) para regresar al mismo lugar
 *
 * Los botones que lo abren llevan data-accion, data-titulo, data-modo ('crear' o
 * 'editar') y data-campos (ver app.js). En modo editar, app.js deja el código en solo
 * lectura ([data-bloquear-al-editar]) y muestra u oculta [data-solo-al-editar] / [data-solo-al-crear].
 */
$modoFormulario = $formulario['modo'] ?? 'crear';
$editando = $modoFormulario === 'editar';
$valoresFormulario = $formulario['valores'] ?? ['cantidad' => 1];
$erroresFormulario = $formulario['errores'] ?? [];
$valor = function (string $campo) use ($valoresFormulario): string {
    return (string) ($valoresFormulario[$campo] ?? '');
};

// Imprime is-invalid y el mensaje de error de un campo (si lo tiene).
$claseError = function (string $campo) use ($erroresFormulario): string {
    return isset($erroresFormulario[$campo]) ? ' is-invalid' : '';
};
$mensajeError = function (string $campo) use ($erroresFormulario): string {
    return isset($erroresFormulario[$campo])
        ? '<div class="invalid-feedback">' . e($erroresFormulario[$campo]) . '</div>'
        : '';
};
// Visible solo al crear o solo al editar (app.js lo cambia según el botón que abre el modal).
$soloAlCrear = 'data-solo-al-crear' . ($editando ? ' hidden' : '');
$soloAlEditar = 'data-solo-al-editar' . ($editando ? '' : ' hidden');

// La ficha técnica se abre sola si el error está en uno de sus campos.
$camposFicha = ['marca', 'modelo', 'numeroSerie', 'valorReferencia', 'descripcion', 'caracteristicasTecnicas', 'condicionesUso'];
$abrirFicha = (bool) array_intersect($camposFicha, array_keys($erroresFormulario));

$urlGuardarEquipo = url('/admin/equipos/guardar');
$urlActualizarEquipo = url('/admin/equipos/actualizar');
?>
<div class="modal fade modal-er" id="modalEquipo" tabindex="-1" aria-labelledby="modalEquipoTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formulario ? '1' : '0' ?>">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <?php // El <form> ES el .modal-content: así Bootstrap puede desplazar el cuerpo si no cabe en pantalla. ?>
        <form method="post" action="<?= e($editando ? $urlActualizarEquipo : $urlGuardarEquipo) ?>"
              class="modal-content" novalidate data-enviando="Guardando…">
                <span class="franja-precaucion" aria-hidden="true"></span>
                <div class="modal-header">
                    <span class="marca-icono marca-icono-38" aria-hidden="true">
                        <i class="bi bi-plus-lg" <?= $soloAlCrear ?>></i><i class="bi bi-pencil-fill" <?= $soloAlEditar ?>></i>
                    </span>
                    <div>
                        <h2 class="modal-title" id="modalEquipoTitulo"><?= $editando ? 'Editar equipo' : 'Registrar equipo' ?></h2>
                        <p class="modal-subtitulo" <?= $soloAlCrear ?>>Panel de inventario y maquinaria</p>
                        <p class="modal-subtitulo" <?= $soloAlEditar ?>>Modificar ficha técnica y estado</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <?= csrfCampo() ?>
                    <input type="hidden" name="idEquipo" value="<?= e($formulario['idEquipo'] ?? '') ?>">
                    <input type="hidden" name="origen" value="<?= e($origen) ?>">
                    <input type="hidden" name="volver" value="<?= e($volver) ?>">

                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <label for="codigoInventario" class="form-label"><i class="bi bi-hash" aria-hidden="true"></i> ID del equipo</label>
                            <input type="text" id="codigoInventario" name="codigoInventario" maxlength="30" required data-bloquear-al-editar
                                   class="form-control campo-mayusculas<?= $claseError('codigoInventario') ?>" placeholder="Ej. EQ-1005"
                                   value="<?= e($valor('codigoInventario')) ?>"<?= $editando ? ' readonly' : '' ?>>
                            <?= $mensajeError('codigoInventario') ?>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="nombre" class="form-label"><i class="bi bi-card-heading" aria-hidden="true"></i> Nombre del equipo</label>
                            <input type="text" id="nombre" name="nombre" maxlength="120" required
                                   class="form-control<?= $claseError('nombre') ?>" placeholder="Ej. Retroexcavadora John Deere 310L"
                                   value="<?= e($valor('nombre')) ?>">
                            <?= $mensajeError('nombre') ?>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="idCategoria" class="form-label"><i class="bi bi-tags-fill" aria-hidden="true"></i> Categoría</label>
                            <select id="idCategoria" name="idCategoria" required class="form-select<?= $claseError('idCategoria') ?>">
                                <option value="">Elige una</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= e($categoria['idCategoria']) ?>"<?= $valor('idCategoria') === (string) $categoria['idCategoria'] ? ' selected' : '' ?>>
                                        <?= e($categoria['nombre']) ?><?= $categoria['activa'] ? '' : ' (inactiva)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= $mensajeError('idCategoria') ?>
                        </div>
                        <div class="col-md-6 col-lg-2" <?= $soloAlCrear ?>>
                            <label for="cantidad" class="form-label"><i class="bi bi-123" aria-hidden="true"></i> Cantidad</label>
                            <input type="number" id="cantidad" name="cantidad" min="1" max="<?= Equipo::MAXIMO_UNIDADES ?>" step="1"
                                   class="form-control<?= $claseError('cantidad') ?>" value="<?= e($valor('cantidad') ?: '1') ?>">
                            <?= $mensajeError('cantidad') ?>
                        </div>
                        <?php // Al editar, en lugar de Cantidad: las unidades de este modelo (solo se muestran). ?>
                        <div class="col-md-6 col-lg-2" <?= $soloAlEditar ?>>
                            <span class="form-label"><i class="bi bi-boxes" aria-hidden="true"></i> Unidades</span>
                            <p class="unidades-modelo mb-0">Unidades de este modelo: <strong data-campo-html="unidadesModelo"><?= e($valor('unidadesModelo')) ?></strong></p>
                            <input type="hidden" name="unidadesModelo" value="<?= e($valor('unidadesModelo')) ?>">
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <label for="fechaAdquisicion" class="form-label"><i class="bi bi-calendar-event" aria-hidden="true"></i> Fecha de compra <span class="etiqueta-opcional">(opcional)</span></label>
                            <input type="date" id="fechaAdquisicion" name="fechaAdquisicion" max="<?= e(date('Y-m-d')) ?>"
                                   class="form-control<?= $claseError('fechaAdquisicion') ?>" value="<?= e($valor('fechaAdquisicion')) ?>">
                            <?= $mensajeError('fechaAdquisicion') ?>
                        </div>
                        <?php // Estado: solo lectura (se cambia con "Cambiar estado" del detalle). ?>
                        <div class="col-lg-8" <?= $soloAlEditar ?>>
                            <div class="recuadro-estado">
                                <span class="form-label mb-0"><i class="bi bi-activity" aria-hidden="true"></i> Estado</span>
                                <span data-campo-html="estadoEtiqueta"><?= $valor('estadoActual') !== '' ? estadoBadge($valor('estadoActual')) : '' ?></span>
                                <span class="form-text m-0">Se cambia con "Cambiar estado" en el detalle del equipo.</span>
                            </div>
                            <input type="hidden" name="estadoActual" value="<?= e($valor('estadoActual')) ?>">
                        </div>
                        <div class="col-lg-8 pt-lg-4" <?= $soloAlCrear ?>>
                            <p class="form-text mb-0">Con una cantidad mayor que 1 se registran unidades con códigos seguidos
                                (EQ-1005, EQ-1006…) y sin número de serie.</p>
                        </div>
                    </div>

                    <?php // <details>: sección plegable sin JavaScript. ?>
                    <details class="ficha-plegable"<?= $abrirFicha ? ' open' : '' ?>>
                        <summary>Ficha técnica <span class="etiqueta-opcional">(opcional)</span></summary>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="marca" class="form-label"><i class="bi bi-award" aria-hidden="true"></i> Marca</label>
                                <input type="text" id="marca" name="marca" maxlength="80" class="form-control<?= $claseError('marca') ?>"
                                       placeholder="Ej. Caterpillar" value="<?= e($valor('marca')) ?>">
                                <?= $mensajeError('marca') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="modelo" class="form-label"><i class="bi bi-gear" aria-hidden="true"></i> Modelo</label>
                                <input type="text" id="modelo" name="modelo" maxlength="80" class="form-control<?= $claseError('modelo') ?>"
                                       placeholder="Ej. 320D" value="<?= e($valor('modelo')) ?>">
                                <?= $mensajeError('modelo') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="numeroSerie" class="form-label"><i class="bi bi-upc" aria-hidden="true"></i> Número de serie</label>
                                <input type="text" id="numeroSerie" name="numeroSerie" maxlength="80"
                                       class="form-control<?= $claseError('numeroSerie') ?>" placeholder="Ej. CAT-320D-0003"
                                       value="<?= e($valor('numeroSerie')) ?>">
                                <?= $mensajeError('numeroSerie') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="valorReferencia" class="form-label"><i class="bi bi-currency-dollar" aria-hidden="true"></i> Valor de referencia</label>
                                <input type="number" id="valorReferencia" name="valorReferencia" min="0" step="0.01"
                                       class="form-control<?= $claseError('valorReferencia') ?>" placeholder="Ej. 85000.00"
                                       value="<?= e($valor('valorReferencia')) ?>">
                                <?= $mensajeError('valorReferencia') ?>
                            </div>
                            <div class="col-12">
                                <label for="descripcion" class="form-label"><i class="bi bi-card-text" aria-hidden="true"></i> Descripción</label>
                                <textarea id="descripcion" name="descripcion" rows="2" maxlength="2000"
                                          class="form-control<?= $claseError('descripcion') ?>"
                                          placeholder="Para qué sirve y en qué obras se usa..."><?= e($valor('descripcion')) ?></textarea>
                                <?= $mensajeError('descripcion') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="caracteristicasTecnicas" class="form-label"><i class="bi bi-list-check" aria-hidden="true"></i> Características técnicas</label>
                                <textarea id="caracteristicasTecnicas" name="caracteristicasTecnicas" rows="3" maxlength="2000"
                                          class="form-control<?= $claseError('caracteristicasTecnicas') ?>"
                                          placeholder="Potencia: 148 HP; Peso operativo: 20 t..."><?= e($valor('caracteristicasTecnicas')) ?></textarea>
                                <?= $mensajeError('caracteristicasTecnicas') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="condicionesUso" class="form-label"><i class="bi bi-shield-check" aria-hidden="true"></i> Condiciones de uso</label>
                                <textarea id="condicionesUso" name="condicionesUso" rows="3" maxlength="2000"
                                          class="form-control<?= $claseError('condicionesUso') ?>"
                                          placeholder="Requiere operador certificado..."><?= e($valor('condicionesUso')) ?></textarea>
                                <?= $mensajeError('condicionesUso') ?>
                            </div>
                        </div>
                    </details>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                        <span <?= $soloAlCrear ?>>Registrar</span><span <?= $soloAlEditar ?>>Actualizar</span></button>
                </div>
        </form>
    </div>
</div>
