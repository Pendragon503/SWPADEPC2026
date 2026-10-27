<?php
/**
 * Listado de categorías + modal para crear y editar.
 *
 * MÓDULO DE EJEMPLO. Puntos a copiar en otros módulos:
 *  - Todo dato que viene de la base o del usuario se imprime con e(), que convierte
 *    < > " ' & en texto. Así un nombre como <script>...</script> se ve como texto
 *    y no se ejecuta (XSS). Excepción: estadoBadge() y csrfCampo() ya devuelven HTML seguro.
 *  - Todo formulario POST lleva csrfCampo(): un token secreto de la sesión que el
 *    Router comprueba. Evita que otra página envíe formularios en nombre del usuario (CSRF).
 *  - Los formularios llevan en campos ocultos la búsqueda y la página actuales,
 *    para volver al mismo lugar del listado después de guardar.
 *
 * @var array      $categorias
 * @var string     $buscar
 * @var int        $pagina
 * @var int        $totalPaginas
 * @var int        $total
 * @var int        $desplazamiento
 * @var array|null $formulario  datos del último envío con errores (o null)
 */

// ---------------------------------------------------------------------
// Estado inicial del modal.
// Normalmente arranca vacío en modo "crear"; los botones "Nueva categoría" y
// "Editar" lo cambian con JavaScript antes de abrirlo (ver app.js).
// Si el último envío tuvo errores, arranca con lo que el usuario escribió,
// los errores, y se abre solo al cargar la página (data-abrir-al-cargar="1").
// ---------------------------------------------------------------------
$modo = $formulario['modo'] ?? 'crear';
$valores = [
    'idCategoria' => $formulario['idCategoria'] ?? '',
    'nombre'      => $formulario['nombre'] ?? '',
    'descripcion' => $formulario['descripcion'] ?? '',
];
$errores = $formulario['errores'] ?? [];

$urlGuardar = url('/admin/categorias/guardar');
$urlActualizar = url('/admin/categorias/actualizar');
?>
<?php $pestana = 'categorias'; require RAIZ . '/app/views/configuracion/_encabezado.php'; ?>
<div class="encabezado-pagina">
    <div>
        <p class="encabezado-pagina-texto">Clasificación de los equipos del inventario. El código (CAT-01…) se calcula a partir del número de la categoría.</p>
    </div>
    <?php // Abre el modal en modo "crear": data-campos deja todos los campos vacíos. ?>
    <button type="button" class="btn btn-primary"
            data-bs-toggle="modal" data-bs-target="#modalCategoria"
            data-accion="<?= e($urlGuardar) ?>"
            data-titulo="Crear categoría"
            data-campos="<?= e(json_encode(['idCategoria' => '', 'nombre' => '', 'descripcion' => ''])) ?>">
        <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Crear categoría
    </button>
</div>

<section class="panel">
    <div class="panel-cabecera">
        <?php // Búsqueda con GET: el filtro queda en la URL y se puede recargar o compartir. ?>
        <form method="get" action="<?= e(url('/admin/configuracion')) ?>" class="buscador-tabla" role="search">
            <input type="hidden" name="tab" value="categorias">
            <div class="buscador-tabla-campo">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="buscar" class="visually-hidden">Buscar categorías</label>
                <input type="search" id="buscar" name="buscar" class="form-control"
                       placeholder="Buscar por nombre o descripción..." value="<?= e($buscar) ?>">
            </div>
            <button type="submit" class="btn btn-secondary">Buscar</button>
            <?php if ($buscar !== ''): ?>
                <a href="<?= e(url('/admin/configuracion?tab=categorias')) ?>" class="btn btn-claro">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="panel-cuerpo">
        <?php if (!$categorias): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-tags" aria-hidden="true"></i></span>
                <?php if ($buscar !== ''): ?>
                    <p class="estado-vacio-titulo">No hay categorías que coincidan con "<?= e($buscar) ?>"</p>
                    <p class="estado-vacio-texto">Prueba con otra palabra o limpia la búsqueda.</p>
                <?php else: ?>
                    <p class="estado-vacio-titulo">Todavía no hay categorías</p>
                    <p class="estado-vacio-texto">Crea la primera con el botón "Crear categoría".</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Nombre</th>
                        <th scope="col" class="d-none d-md-table-cell">Descripción</th>
                        <th scope="col" class="text-center">Equipos asociados</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="col-acciones">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categorias as $categoria): ?>
                        <?php
                        $activa = (bool) $categoria['activa'];
                        // Datos que el botón "Editar" copia al modal (ver app.js).
                        $camposEditar = [
                            'idCategoria' => $categoria['idCategoria'],
                            'nombre'      => $categoria['nombre'],
                            'descripcion' => $categoria['descripcion'] ?? '',
                        ];
                        ?>
                        <tr>
                            <td><span class="codigo"><?= e(codigo('CAT', (int) $categoria['idCategoria'])) ?></span></td>
                            <td class="texto-fuerte"><?= e($categoria['nombre']) ?></td>
                            <td class="texto-suave d-none d-md-table-cell"><?= $categoria['descripcion'] !== null ? e($categoria['descripcion']) : '—' ?></td>
                            <td class="text-center"><?= e($categoria['equiposActivos']) ?></td>
                            <td><?= estadoBadge($activa ? 'Activa' : 'Inactiva') ?></td>
                            <td class="col-acciones">
                                <button type="button" class="btn btn-secondary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#modalCategoria"
                                        data-accion="<?= e($urlActualizar) ?>"
                                        data-titulo="Editar categoría"
                                        data-campos="<?= e(json_encode($camposEditar)) ?>">
                                    <i class="bi bi-pencil-fill" aria-hidden="true"></i> Editar
                                </button>

                                <?php // Activar / desactivar: un formulario POST por fila (nunca un enlace GET,
                                      // porque un GET no debe cambiar datos). ?>
                                <form method="post" action="<?= e(url('/admin/categorias/cambiar-estado')) ?>" class="d-inline"
                                    <?php if ($activa): ?>
                                      data-confirmar="¿Desactivar la categoría &quot;<?= e($categoria['nombre']) ?>&quot;? Dejará de aparecer al registrar equipos."
                                    <?php endif; ?>>
                                    <?= csrfCampo() ?>
                                    <input type="hidden" name="idCategoria" value="<?= e($categoria['idCategoria']) ?>">
                                    <input type="hidden" name="activar" value="<?= $activa ? '0' : '1' ?>">
                                    <input type="hidden" name="buscar" value="<?= e($buscar) ?>">
                                    <input type="hidden" name="pagina" value="<?= e($pagina) ?>">
                                    <?php if ($activa): ?>
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-slash-circle" aria-hidden="true"></i> Desactivar
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-check-circle" aria-hidden="true"></i> Activar
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pie-tabla">
                <p class="pie-tabla-texto">
                    Mostrando <?= e($desplazamiento + 1) ?>–<?= e($desplazamiento + count($categorias)) ?>
                    de <?= e($total) ?> <?= $total === 1 ? 'categoría' : 'categorías' ?>
                </p>
                <?php
                $rutaPaginacion = '/admin/configuracion';
                $parametrosPaginacion = ['tab' => 'categorias', 'buscar' => $buscar];
                require RAIZ . '/app/views/componentes/paginacion.php';
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
/* =====================================================================
   Modal de crear / editar (componente .modal-er).
   Es UN solo formulario POST normal (sin AJAX):
     - "Nueva categoría" pone action = guardar y vacía los campos.
     - "Editar" pone action = actualizar y copia los datos de la fila.
   Si el servidor encontró errores, el modal se dibuja ya con el action, los
   valores y los mensajes correctos, y data-abrir-al-cargar = 1 hace que
   app.js lo abra solo al cargar la página.
   ===================================================================== */
?>
<div class="modal fade modal-er" id="modalCategoria" tabindex="-1" aria-labelledby="modalCategoriaTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formulario ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= e($modo === 'editar' ? $urlActualizar : $urlGuardar) ?>" novalidate>
                <div class="modal-header">
                    <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-tags"></i></span>
                    <div>
                        <h2 class="modal-title" id="modalCategoriaTitulo"><?= $modo === 'editar' ? 'Editar categoría' : 'Crear categoría' ?></h2>
                        <p class="modal-subtitulo">Configuración del catálogo</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <?= csrfCampo() ?>
                    <input type="hidden" name="idCategoria" value="<?= e($valores['idCategoria']) ?>">
                    <input type="hidden" name="buscar" value="<?= e($buscar) ?>">
                    <input type="hidden" name="pagina" value="<?= e($pagina) ?>">

                    <div>
                        <label for="nombre" class="form-label"><i class="bi bi-tags-fill" aria-hidden="true"></i> Nombre de la categoría</label>
                        <?php // is-invalid pinta el campo en rojo y muestra el .invalid-feedback de abajo. ?>
                        <input type="text" id="nombre" name="nombre" maxlength="80" required
                               class="form-control<?= isset($errores['nombre']) ? ' is-invalid' : '' ?>"
                               placeholder="Ej. Plataformas elevadoras" value="<?= e($valores['nombre']) ?>"
                               <?= isset($errores['nombre']) ? 'aria-describedby="errorNombre"' : '' ?>>
                        <?php if (isset($errores['nombre'])): ?>
                            <div class="invalid-feedback" id="errorNombre"><?= e($errores['nombre']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="descripcion" class="form-label">
                            <i class="bi bi-card-text" aria-hidden="true"></i> Descripción <span class="etiqueta-opcional">(opcional)</span>
                        </label>
                        <textarea id="descripcion" name="descripcion" rows="4" maxlength="250"
                                  class="form-control<?= isset($errores['descripcion']) ? ' is-invalid' : '' ?>"
                                  placeholder="Para qué se usan los equipos de esta categoría..."
                                  <?= isset($errores['descripcion']) ? 'aria-describedby="errorDescripcion"' : '' ?>><?= e($valores['descripcion']) ?></textarea>
                        <?php if (isset($errores['descripcion'])): ?>
                            <div class="invalid-feedback" id="errorDescripcion"><?= e($errores['descripcion']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
