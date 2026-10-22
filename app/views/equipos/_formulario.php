<?php
/**
 * Modal de crear / editar equipo (componente .modal-er, ancho y en dos columnas).
 * Se incluye desde equipos/index.php y equipos/detalle.php.
 *
 * Antes de incluirlo, la vista define:
 *   $formulario  datos del último envío con errores, o null (los deja EquipoController)
 *   $categorias  todas las categorías; las inactivas se marcan "(inactiva)" y el modelo
 *                solo acepta una inactiva si es la que el equipo ya tenía
 *   $origen      'listado' o 'detalle': a dónde vuelve el formulario después de guardar
 *   $volver      filtros del listado (query string) para regresar al mismo lugar
 *
 * Los botones que lo abren llevan data-accion, data-titulo, data-modo ('crear' o
 * 'editar') y data-campos (ver app.js). En modo editar, app.js deja el código
 * en solo lectura ([data-bloquear-al-editar]) y muestra el estado ([data-solo-al-editar]).
 */
$modoFormulario = $formulario['modo'] ?? 'crear';
$valoresFormulario = $formulario['valores'] ?? [];
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

$urlGuardarEquipo = url('/admin/equipos/guardar');
$urlActualizarEquipo = url('/admin/equipos/actualizar');
?>
<div class="modal fade modal-er" id="modalEquipo" tabindex="-1" aria-labelledby="modalEquipoTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formulario ? '1' : '0' ?>">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <?php // El <form> ES el .modal-content: así Bootstrap puede desplazar el cuerpo si no cabe en pantalla. ?>
        <form method="post" action="<?= e($modoFormulario === 'editar' ? $urlActualizarEquipo : $urlGuardarEquipo) ?>"
              class="modal-content" novalidate>
                <div class="modal-header">
                    <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                    <div>
                        <h2 class="modal-title" id="modalEquipoTitulo"><?= $modoFormulario === 'editar' ? 'Editar equipo' : 'Registrar equipo' ?></h2>
                        <p class="modal-subtitulo">Inventario y maquinaria</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <?= csrfCampo() ?>
                    <input type="hidden" name="idEquipo" value="<?= e($formulario['idEquipo'] ?? '') ?>">
                    <input type="hidden" name="origen" value="<?= e($origen) ?>">
                    <input type="hidden" name="volver" value="<?= e($volver) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="codigoInventario" class="form-label"><i class="bi bi-hash" aria-hidden="true"></i> Código de inventario</label>
                            <input type="text" id="codigoInventario" name="codigoInventario" maxlength="30" required data-bloquear-al-editar
                                   class="form-control campo-mayusculas<?= $claseError('codigoInventario') ?>" placeholder="Ej. EQ-1005"
                                   value="<?= e($valor('codigoInventario')) ?>"<?= $modoFormulario === 'editar' ? ' readonly' : '' ?>>
                            <?= $mensajeError('codigoInventario') ?>
                        </div>
                        <div class="col-md-6">
                            <label for="numeroSerie" class="form-label"><i class="bi bi-upc" aria-hidden="true"></i> Número de serie <span class="etiqueta-opcional">(opcional)</span></label>
                            <input type="text" id="numeroSerie" name="numeroSerie" maxlength="80"
                                   class="form-control<?= $claseError('numeroSerie') ?>" placeholder="Ej. CAT-320D-0003"
                                   value="<?= e($valor('numeroSerie')) ?>">
                            <?= $mensajeError('numeroSerie') ?>
                        </div>

                        <div class="col-12">
                            <label for="nombre" class="form-label"><i class="bi bi-card-heading" aria-hidden="true"></i> Nombre del equipo</label>
                            <input type="text" id="nombre" name="nombre" maxlength="120" required
                                   class="form-control<?= $claseError('nombre') ?>" placeholder="Ej. Retroexcavadora John Deere 310L"
                                   value="<?= e($valor('nombre')) ?>">
                            <?= $mensajeError('nombre') ?>
                        </div>

                        <div class="col-md-6">
                            <label for="idCategoria" class="form-label"><i class="bi bi-tags-fill" aria-hidden="true"></i> Categoría</label>
                            <select id="idCategoria" name="idCategoria" required class="form-select<?= $claseError('idCategoria') ?>">
                                <option value="">Elige una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= e($categoria['idCategoria']) ?>"<?= $valor('idCategoria') === (string) $categoria['idCategoria'] ? ' selected' : '' ?>>
                                        <?= e($categoria['nombre']) ?><?= $categoria['activa'] ? '' : ' (inactiva)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= $mensajeError('idCategoria') ?>
                        </div>
                        <div class="col-md-6" data-solo-al-editar<?= $modoFormulario === 'editar' ? '' : ' hidden' ?>>
                            <label for="estadoActual" class="form-label"><i class="bi bi-activity" aria-hidden="true"></i> Estado</label>
                            <input type="text" id="estadoActual" name="estadoActual" class="form-control" readonly
                                   value="<?= e($valor('estadoActual')) ?>">
                            <div class="form-text">Se cambia con el botón "Cambiar estado" del detalle.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="marca" class="form-label"><i class="bi bi-award" aria-hidden="true"></i> Marca <span class="etiqueta-opcional">(opcional)</span></label>
                            <input type="text" id="marca" name="marca" maxlength="80" class="form-control<?= $claseError('marca') ?>"
                                   placeholder="Ej. Caterpillar" value="<?= e($valor('marca')) ?>">
                            <?= $mensajeError('marca') ?>
                        </div>
                        <div class="col-md-6">
                            <label for="modelo" class="form-label"><i class="bi bi-gear" aria-hidden="true"></i> Modelo <span class="etiqueta-opcional">(opcional)</span></label>
                            <input type="text" id="modelo" name="modelo" maxlength="80" class="form-control<?= $claseError('modelo') ?>"
                                   placeholder="Ej. 320D" value="<?= e($valor('modelo')) ?>">
                            <?= $mensajeError('modelo') ?>
                        </div>

                        <div class="col-md-6">
                            <label for="fechaAdquisicion" class="form-label"><i class="bi bi-calendar-event" aria-hidden="true"></i> Fecha de compra <span class="etiqueta-opcional">(opcional)</span></label>
                            <input type="date" id="fechaAdquisicion" name="fechaAdquisicion" max="<?= e(date('Y-m-d')) ?>"
                                   class="form-control<?= $claseError('fechaAdquisicion') ?>" value="<?= e($valor('fechaAdquisicion')) ?>">
                            <?= $mensajeError('fechaAdquisicion') ?>
                        </div>
                        <div class="col-md-6">
                            <label for="valorReferencia" class="form-label"><i class="bi bi-currency-dollar" aria-hidden="true"></i> Valor de referencia <span class="etiqueta-opcional">(opcional)</span></label>
                            <input type="number" id="valorReferencia" name="valorReferencia" min="0" step="0.01"
                                   class="form-control<?= $claseError('valorReferencia') ?>" placeholder="Ej. 85000.00"
                                   value="<?= e($valor('valorReferencia')) ?>">
                            <?= $mensajeError('valorReferencia') ?>
                        </div>

                        <div class="col-12">
                            <label for="descripcion" class="form-label"><i class="bi bi-card-text" aria-hidden="true"></i> Descripción <span class="etiqueta-opcional">(opcional)</span></label>
                            <textarea id="descripcion" name="descripcion" rows="2" maxlength="2000"
                                      class="form-control<?= $claseError('descripcion') ?>"
                                      placeholder="Para qué sirve y en qué obras se usa..."><?= e($valor('descripcion')) ?></textarea>
                            <?= $mensajeError('descripcion') ?>
                        </div>
                        <div class="col-md-6">
                            <label for="caracteristicasTecnicas" class="form-label"><i class="bi bi-list-check" aria-hidden="true"></i> Características técnicas <span class="etiqueta-opcional">(opcional)</span></label>
                            <textarea id="caracteristicasTecnicas" name="caracteristicasTecnicas" rows="3" maxlength="2000"
                                      class="form-control<?= $claseError('caracteristicasTecnicas') ?>"
                                      placeholder="Potencia: 148 HP; Peso operativo: 20 t..."><?= e($valor('caracteristicasTecnicas')) ?></textarea>
                            <?= $mensajeError('caracteristicasTecnicas') ?>
                        </div>
                        <div class="col-md-6">
                            <label for="condicionesUso" class="form-label"><i class="bi bi-shield-check" aria-hidden="true"></i> Condiciones de uso <span class="etiqueta-opcional">(opcional)</span></label>
                            <textarea id="condicionesUso" name="condicionesUso" rows="3" maxlength="2000"
                                      class="form-control<?= $claseError('condicionesUso') ?>"
                                      placeholder="Requiere operador certificado..."><?= e($valor('condicionesUso')) ?></textarea>
                            <?= $mensajeError('condicionesUso') ?>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Guardar</button>
                </div>
        </form>
    </div>
</div>
