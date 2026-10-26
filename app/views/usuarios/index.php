<?php
/**
 * Usuarios y roles (RF-02): administradores y operadores, con el modal de crear/editar.
 * Sin frame en Figma: patrón de Categorías y Clientes.
 *
 * @var array      $usuarios    Usuario::listar()
 * @var array      $filtros     ['buscar', 'rol', 'activo']
 * @var array|null $formulario  modal con errores: ['valores', 'errores'] (o null)
 * @var int        $total, $pagina, $totalPaginas, $desplazamiento
 */
$volver = http_build_query($filtros + ['pagina' => $pagina]);
$valores = $formulario['valores'] ?? array_fill_keys(Usuario::CAMPOS_FORMULARIO, '') + ['rol' => 'Operador'];
$errores = $formulario['errores'] ?? [];
$editando = ($valores['idUsuario'] ?? '') !== '' && (int) $valores['idUsuario'] > 0;

// [campo, etiqueta, icono, tipo, largo, ejemplo, opcional]
$campos = [
    ['nombres', 'Nombres', 'bi-person', 'text', 80, 'Ej. Ana María', false],
    ['apellidos', 'Apellidos', 'bi-person', 'text', 80, 'Ej. Gómez', false],
    ['documentoIdentidad', 'Documento de identidad', 'bi-person-vcard', 'text', 25, 'Ej. 01234567-8', false],
    ['correo', 'Correo (usuario)', 'bi-envelope', 'email', 120, 'Ej. ana@equiprent.com', false],
    ['telefono', 'Teléfono', 'bi-telephone', 'tel', 20, 'Ej. 7000-0000', true],
    ['direccion', 'Dirección', 'bi-geo-alt', 'text', 250, 'Ej. San Salvador', true],
];
?>
<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo">Usuarios y roles</h2>
        <p class="encabezado-pagina-texto">Personal que usa el sistema. Los clientes se gestionan en Clientes.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario"
            data-accion="<?= e(url('/admin/usuarios/guardar')) ?>" data-titulo="Crear usuario" data-modo="crear"
            data-campos="<?= e(json_encode(array_fill_keys(Usuario::CAMPOS_FORMULARIO, '') + ['rol' => 'Operador'])) ?>">
        <i class="bi bi-person-plus-fill" aria-hidden="true"></i> Crear usuario
    </button>
</div>

<section class="panel">
    <div class="panel-cabecera">
        <form method="get" action="<?= e(url('/admin/usuarios')) ?>" class="filtros-tabla filtros-tabla-3" role="search">
            <div class="buscador-tabla-campo filtros-tabla-texto">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="buscar" class="visually-hidden">Buscar</label>
                <input type="search" id="buscar" name="buscar" class="form-control" placeholder="Nombre, correo o documento..." value="<?= e($filtros['buscar']) ?>">
            </div>
            <div>
                <label for="filtroRol" class="visually-hidden">Rol</label>
                <select id="filtroRol" name="rol" class="form-select">
                    <option value="">Todos los roles</option>
                    <?php foreach (Usuario::ROLES_INTERNOS as $rol): ?>
                        <option<?= $rol === $filtros['rol'] ? ' selected' : '' ?>><?= e($rol) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filtroActivo" class="visually-hidden">Activos o desactivados</label>
                <select id="filtroActivo" name="activo" class="form-select">
                    <option value="1"<?= $filtros['activo'] === '1' ? ' selected' : '' ?>>Activos</option>
                    <option value="0"<?= $filtros['activo'] === '0' ? ' selected' : '' ?>>Desactivados</option>
                    <option value="todos"<?= $filtros['activo'] === 'todos' ? ' selected' : '' ?>>Todos</option>
                </select>
            </div>
            <div class="filtros-tabla-botones">
                <button type="submit" class="btn btn-secondary">Filtrar</button>
                <a href="<?= e(url('/admin/usuarios')) ?>" class="btn btn-claro">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="panel-cuerpo">
        <?php if (!$usuarios): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-people" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">No hay usuarios con esos filtros</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col" class="d-none d-md-table-cell">Correo</th>
                        <th scope="col">Rol</th>
                        <th scope="col" class="d-none d-lg-table-cell">Último acceso</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="col-acciones">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <?php $esYo = (int) $usuario['idUsuario'] === Auth::id(); ?>
                        <tr>
                            <td>
                                <span class="texto-fuerte"><?= e($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></span>
                                <?php if ($esYo): ?><span class="estado estado-azul">Tú</span><?php endif; ?>
                                <br><span class="texto-suave small"><?= e($usuario['documentoIdentidad'] ?? '—') ?></span>
                            </td>
                            <td class="d-none d-md-table-cell"><?= e($usuario['correo']) ?></td>
                            <td><?= e($usuario['rol']) ?></td>
                            <td class="d-none d-lg-table-cell text-nowrap"><?= e(fechaCorta($usuario['ultimoAcceso'], true)) ?></td>
                            <td><?= estadoBadge($usuario['activo'] ? 'Activo' : 'Inactivo') ?></td>
                            <td class="col-acciones text-nowrap">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario"
                                        data-accion="<?= e(url('/admin/usuarios/actualizar')) ?>" data-titulo="Editar usuario" data-modo="editar"
                                        data-campos="<?= e(json_encode(Usuario::camposFormulario($usuario))) ?>"
                                        title="Editar" aria-label="Editar <?= e($usuario['correo']) ?>">
                                    <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                </button>
                                <?php if (!$esYo): // no puedes desactivarte a ti mismo ?>
                                    <form method="post" action="<?= e(url('/admin/usuarios/cambiar-estado')) ?>" class="d-inline"
                                        <?= $usuario['activo'] ? 'data-confirmar="¿Desactivar a ' . e($usuario['correo']) . '? Perderá el acceso de inmediato."' : '' ?>>
                                        <?= csrfCampo() ?>
                                        <input type="hidden" name="idUsuario" value="<?= e($usuario['idUsuario']) ?>">
                                        <input type="hidden" name="activar" value="<?= $usuario['activo'] ? '0' : '1' ?>">
                                        <input type="hidden" name="volver" value="<?= e($volver) ?>">
                                        <button type="submit" class="btn btn-sm <?= $usuario['activo'] ? 'btn-danger' : 'btn-primary' ?>"
                                                title="<?= $usuario['activo'] ? 'Desactivar' : 'Activar' ?>" aria-label="<?= $usuario['activo'] ? 'Desactivar' : 'Activar' ?> <?= e($usuario['correo']) ?>">
                                            <i class="bi <?= $usuario['activo'] ? 'bi-slash-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="pie-tabla">
                <p class="pie-tabla-texto">Mostrando <?= e($desplazamiento + 1) ?>–<?= e($desplazamiento + count($usuarios)) ?> de <?= e($total) ?> usuarios</p>
                <?php
                $rutaPaginacion = '/admin/usuarios';
                $parametrosPaginacion = $filtros;
                require RAIZ . '/app/views/componentes/paginacion.php';
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="modal fade modal-er" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formulario ? '1' : '0' ?>">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= e(url($editando ? '/admin/usuarios/actualizar' : '/admin/usuarios/guardar')) ?>" class="modal-content" novalidate data-enviando="Guardando…">
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                <div>
                    <h2 class="modal-title" id="modalUsuarioTitulo"><?= $editando ? 'Editar usuario' : 'Crear usuario' ?></h2>
                    <p class="modal-subtitulo">Administrador u operador</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?= csrfCampo() ?>
                <input type="hidden" name="idUsuario" value="<?= e($valores['idUsuario']) ?>">
                <input type="hidden" name="volver" value="<?= e($volver) ?>">
                <div class="row g-3">
                    <?php foreach ($campos as [$campo, $etiqueta, $icono, $tipo, $largo, $ejemplo, $opcional]): ?>
                        <div class="col-md-6">
                            <label for="usu_<?= e($campo) ?>" class="form-label"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i> <?= e($etiqueta) ?>
                                <?= $opcional ? '<span class="etiqueta-opcional">(opcional)</span>' : '' ?></label>
                            <input type="<?= e($tipo) ?>" id="usu_<?= e($campo) ?>" name="<?= e($campo) ?>" maxlength="<?= e($largo) ?>" placeholder="<?= e($ejemplo) ?>"
                                   class="form-control<?= isset($errores[$campo]) ? ' is-invalid' : '' ?>" value="<?= e($valores[$campo]) ?>">
                            <?php if (isset($errores[$campo])): ?><div class="invalid-feedback"><?= e($errores[$campo]) ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="col-md-6">
                        <label for="usu_rol" class="form-label"><i class="bi bi-shield-lock" aria-hidden="true"></i> Rol</label>
                        <select id="usu_rol" name="rol" class="form-select<?= isset($errores['rol']) ? ' is-invalid' : '' ?>">
                            <?php foreach (Usuario::ROLES_INTERNOS as $rol): ?>
                                <option<?= $rol === $valores['rol'] ? ' selected' : '' ?>><?= e($rol) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errores['rol'])): ?><div class="invalid-feedback"><?= e($errores['rol']) ?></div><?php endif; ?>
                    </div>
                    <?php // Solo al crear (data-solo-al-crear). Sin d-flex: su display !important le gana a "hidden". ?>
                    <div class="col-md-6 pt-md-4" data-solo-al-crear<?= $editando ? ' hidden' : '' ?>>
                        <p class="form-text mb-0">Se generará una contraseña temporal que verás una sola vez al guardar.</p>
                    </div>
                    <div class="col-12" data-solo-al-editar<?= $editando ? '' : ' hidden' ?>>
                        <p class="form-text mb-0">Editar no cambia la contraseña.</p>
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
