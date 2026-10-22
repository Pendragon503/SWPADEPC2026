<?php
/**
 * Clientes (RF-03): filtros, listado y paginación. Lo usan Administrador y Operador.
 *
 * @var array      $clientes
 * @var array      $filtros       ['buscar', 'tipoPersona', 'activo']
 * @var int        $pagina
 * @var int        $totalPaginas
 * @var int        $total
 * @var int        $desplazamiento
 * @var array|null $formulario    modal de crear/editar con errores (o null)
 */

// Filtros y página actuales: los formularios POST los envían en "volver" para regresar al mismo lugar.
$volver = http_build_query($filtros + ['pagina' => $pagina]);
?>
<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo">Clientes</h2>
        <p class="encabezado-pagina-texto">Personas y empresas que alquilan equipos. Cada cliente tiene su usuario para el portal.</p>
    </div>
</div>

<section class="panel">
    <div class="panel-cabecera">
        <form method="get" action="<?= e(url('/clientes')) ?>" class="filtros-tabla filtros-tabla-3" role="search">
            <div class="buscador-tabla-campo filtros-tabla-texto">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="buscar" class="visually-hidden">Buscar clientes</label>
                <input type="search" id="buscar" name="buscar" class="form-control"
                       placeholder="Código, nombre, razón social, documento o correo..." value="<?= e($filtros['buscar']) ?>">
            </div>
            <div>
                <label for="filtroTipo" class="visually-hidden">Tipo de persona</label>
                <select id="filtroTipo" name="tipoPersona" class="form-select">
                    <option value="">Todos los tipos</option>
                    <?php foreach (Cliente::TIPOS as $valor => $texto): ?>
                        <option value="<?= e($valor) ?>"<?= $valor === $filtros['tipoPersona'] ? ' selected' : '' ?>><?= e($texto) ?></option>
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
                <a href="<?= e(url('/clientes')) ?>" class="btn btn-claro">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="panel-cuerpo">
        <?php if (!$clientes): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">No hay clientes con esos filtros</p>
                <p class="estado-vacio-texto">Cambia los filtros o presiona "Limpiar".</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Nombre o razón social</th>
                        <th scope="col" class="d-none d-md-table-cell">Tipo</th>
                        <th scope="col" class="d-none d-lg-table-cell">Documento</th>
                        <th scope="col" class="d-none d-lg-table-cell">Teléfono</th>
                        <th scope="col" class="d-none d-md-table-cell">Correo</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="col-acciones">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <?php $esJuridica = $cliente['tipoPersona'] === 'Juridica'; ?>
                        <tr>
                            <td><span class="codigo"><?= e($cliente['codigoCliente'] ?? '—') ?></span></td>
                            <td>
                                <span class="texto-fuerte"><?= e(Cliente::nombreVisible($cliente)) ?></span>
                                <?php if ($esJuridica): ?>
                                    <br><span class="texto-suave small">Contacto: <?= e($cliente['nombres'] . ' ' . $cliente['apellidos']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell"><?= $esJuridica ? 'Jurídica' : 'Natural' ?></td>
                            <td class="d-none d-lg-table-cell text-nowrap">
                                <?= e($cliente['documentoIdentidad'] ?? '—') ?>
                                <?php if ($cliente['documentoTributario'] !== null): ?>
                                    <br><span class="texto-suave small">NIT <?= e($cliente['documentoTributario']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-lg-table-cell text-nowrap"><?= e($cliente['telefono'] ?? '—') ?></td>
                            <td class="d-none d-md-table-cell"><?= e($cliente['correo']) ?></td>
                            <td><?= estadoBadge($cliente['activo'] ? 'Activo' : 'Inactivo') ?></td>
                            <td class="col-acciones"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pie-tabla">
                <p class="pie-tabla-texto">
                    Mostrando <?= e($desplazamiento + 1) ?>–<?= e($desplazamiento + count($clientes)) ?>
                    de <?= e($total) ?> <?= $total === 1 ? 'cliente' : 'clientes' ?>
                </p>
                <?php
                $rutaPaginacion = '/clientes';
                $parametrosPaginacion = $filtros;
                require RAIZ . '/app/views/componentes/paginacion.php';
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>
