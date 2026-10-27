<?php
/**
 * Detalle de un equipo (GET /admin/equipos/detalle?id=5): ficha técnica,
 * fotografías, tarifas e historial de estados. No tiene frame en Figma: usa los
 * componentes base (panel, tabla-er, estadoBadge, modal-er).
 *
 * @var array      $equipo
 * @var array      $historial         bitácora, del más reciente al más antiguo
 * @var array      $fotos             FotografiaEquipo::listarPorEquipo()
 * @var array      $tarifas           Tarifa::listarPorEquipo(), con su situación
 * @var array|null $formularioTarifa  modal de nueva tarifa con errores
 * @var array|null $contratoAbierto   contrato Pendiente/Aprobado/Activo sin devolver, o null
 * @var array      $categorias
 * @var array|null $formulario        modal de editar con errores
 * @var array|null $formularioEstado  modal de cambiar estado con errores
 * @var array|null $formularioBaja    modal de baja con errores
 */
// El Operador ve el equipo en solo consulta: sin botones, formularios ni modales.
$soloConsulta = Auth::rol() !== 'Administrador';
$idEquipo = (int) $equipo['idEquipo'];
$activo = (bool) $equipo['activo'];
$urlDetalle = url('/admin/equipos/detalle?id=' . $idEquipo);

// Si está dado de baja, la "fecha de baja" sale de la bitácora: el último registro de baja.
$baja = null;
if (!$activo) {
    foreach ($historial as $registro) {
        if (strpos($registro['motivo'], 'Baja del equipo') === 0) {
            $baja = $registro;
            break;
        }
    }
}

// El estado solo se cambia a mano si el equipo está activo y no está alquilado.
$puedeCambiarEstado = $activo && $equipo['estado'] !== 'Alquilado';

// Texto con saltos de línea, escapado: e() primero y luego nl2br().
$parrafo = function (?string $texto): string {
    return $texto === null || $texto === '' ? '<span class="texto-suave">—</span>' : nl2br(e($texto));
};
?>
<a href="<?= e(url('/admin/equipos')) ?>" class="enlace-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a equipos</a>

<div class="encabezado-pagina">
    <div>
        <p class="mb-1"><span class="codigo"><?= e($equipo['codigoInventario']) ?></span></p>
        <h2 class="encabezado-pagina-titulo"><?= e($equipo['nombre']) ?></h2>
        <p class="encabezado-pagina-texto">
            <?= estadoBadge($equipo['estado']) ?>
            <?php if (!$activo): ?><?= estadoBadge('Inactivo') ?><?php endif; ?>
            <span class="ms-1"><?= e($equipo['categoria']) ?></span>
        </p>
    </div>
    <?php if (!$soloConsulta): ?>
    <div class="acciones-pagina">
        <a href="<?= e(url('/admin/reportes/equipo?id=' . $idEquipo)) ?>" class="btn btn-secondary">
            <i class="bi bi-clock-history" aria-hidden="true"></i> Historial
        </a>
        <button type="button" class="btn btn-secondary"
                data-bs-toggle="modal" data-bs-target="#modalEquipo"
                data-accion="<?= e(url('/admin/equipos/actualizar')) ?>"
                data-titulo="Editar equipo" data-modo="editar"
                data-campos="<?= e(json_encode(Equipo::camposFormulario($equipo))) ?>">
            <i class="bi bi-pencil-fill" aria-hidden="true"></i> Editar
        </button>
        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalEstado"
                <?= $puedeCambiarEstado ? '' : 'disabled' ?>>
            <i class="bi bi-activity" aria-hidden="true"></i> Cambiar estado
        </button>
        <?php if ($activo): ?>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalBaja">
                <i class="bi bi-slash-circle" aria-hidden="true"></i> Desactivar
            </button>
        <?php else: ?>
            <form method="post" action="<?= e(url('/admin/equipos/cambiar-activo')) ?>" class="d-inline"
                  data-confirmar="¿Reactivar <?= e($equipo['codigoInventario']) ?>? Volverá a estar Disponible.">
                <?= csrfCampo() ?>
                <input type="hidden" name="idEquipo" value="<?= e($idEquipo) ?>">
                <input type="hidden" name="activar" value="1">
                <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reactivar</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (!$activo): ?>
    <div class="alert alert-warning" role="status">
        <i class="bi bi-archive-fill" aria-hidden="true"></i>
        <span>
            <?php if ($baja !== null): ?>
                <strong>Dado de baja el <?= e(fechaCorta($baja['fechaCambio'], true)) ?></strong> por <?= e($baja['usuario']) ?>.
                <?= e($baja['motivo']) ?>. No aparece en el catálogo ni se puede alquilar.
            <?php else: ?>
                <strong>Equipo dado de baja.</strong> No aparece en el catálogo ni se puede alquilar.
            <?php endif; ?>
        </span>
    </div>
<?php elseif ($equipo['estado'] === 'Alquilado'): ?>
    <div class="alert alert-info" role="status">
        <i class="bi bi-truck" aria-hidden="true"></i>
        <span>El equipo está alquilado: su estado cambia cuando Operación registre la devolución.</span>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-xl-7" id="ficha">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Ficha técnica</h3>
            </div>
            <div class="panel-cuerpo">
                <dl class="ficha-datos">
                    <div><dt>Categoría</dt><dd><?= e($equipo['categoria']) ?><?= $equipo['categoriaActiva'] ? '' : ' <span class="texto-suave">(inactiva)</span>' ?></dd></div>
                    <div><dt>Marca</dt><dd><?= e($equipo['marca'] ?? '—') ?></dd></div>
                    <div><dt>Modelo</dt><dd><?= e($equipo['modelo'] ?? '—') ?></dd></div>
                    <div><dt>Número de serie</dt><dd><?= e($equipo['numeroSerie'] ?? '—') ?></dd></div>
                    <div><dt>Fecha de compra</dt><dd><?= e(fechaCorta($equipo['fechaAdquisicion'])) ?></dd></div>
                    <div><dt>Valor de referencia</dt><dd><?= dinero($equipo['valorReferencia']) ?></dd></div>
                    <div><dt>Tarifa diaria vigente</dt><dd><?= dinero($equipo['precioDiario']) ?></dd></div>
                    <div><dt>Depósito sugerido</dt><dd><?= dinero($equipo['depositoSugerido']) ?></dd></div>
                </dl>
                <dl class="ficha-textos">
                    <div><dt>Descripción</dt><dd><?= $parrafo($equipo['descripcion']) ?></dd></div>
                    <div><dt>Características técnicas</dt><dd><?= $parrafo($equipo['caracteristicasTecnicas']) ?></dd></div>
                    <div><dt>Condiciones de uso</dt><dd><?= $parrafo($equipo['condicionesUso']) ?></dd></div>
                </dl>
            </div>
        </section>
    </div>

    <div class="col-xl-5" id="fotos">
        <section class="panel">
            <div class="panel-cabecera">
                <h3 class="panel-titulo">Fotografías</h3>
                <span class="texto-suave small"><?= count($fotos) ?> <?= count($fotos) === 1 ? 'foto' : 'fotos' ?></span>
            </div>
            <div class="panel-cuerpo">
                <?php if (!$fotos): ?>
                    <div class="estado-vacio">
                        <span class="estado-vacio-icono"><i class="bi bi-images" aria-hidden="true"></i></span>
                        <p class="estado-vacio-titulo">Sin fotos</p>
                        <p class="estado-vacio-texto">La primera que subas será la foto principal del catálogo.</p>
                    </div>
                <?php else: ?>
                    <ul class="galeria-fotos">
                        <?php foreach ($fotos as $posicion => $foto): ?>
                            <li class="foto-galeria<?= $foto['esPrincipal'] ? ' es-principal' : '' ?>">
                                <div class="foto-galeria-imagen">
                                    <?php // imagenEquipo() muestra el marcador gris si el archivo no existe (fotos del seed). ?>
                                    <?= imagenEquipo($foto['urlFotografia'], $foto['textoAlternativo'] ?? $equipo['nombre']) ?>
                                    <?php if ($foto['esPrincipal']): ?>
                                        <span class="foto-galeria-etiqueta"><i class="bi bi-star-fill" aria-hidden="true"></i> Principal</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!$soloConsulta): ?>
                                <div class="foto-galeria-acciones">
                                    <?php if (!$foto['esPrincipal']): ?>
                                        <form method="post" action="<?= e(url('/admin/equipos/fotos/principal')) ?>">
                                            <?= csrfCampo() ?>
                                            <input type="hidden" name="idFotografia" value="<?= e($foto['idFotografia']) ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm" title="Marcar como principal" aria-label="Marcar la foto <?= $posicion + 1 ?> como principal">
                                                <i class="bi bi-star" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php foreach (['subir' => 'bi-arrow-up', 'bajar' => 'bi-arrow-down'] as $direccion => $icono): ?>
                                        <?php $esExtremo = $direccion === 'subir' ? $posicion === 0 : $posicion === count($fotos) - 1; ?>
                                        <form method="post" action="<?= e(url('/admin/equipos/fotos/ordenar')) ?>">
                                            <?= csrfCampo() ?>
                                            <input type="hidden" name="idFotografia" value="<?= e($foto['idFotografia']) ?>">
                                            <input type="hidden" name="direccion" value="<?= e($direccion) ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm"<?= $esExtremo ? ' disabled' : '' ?>
                                                    title="<?= $direccion === 'subir' ? 'Mover antes' : 'Mover después' ?>"
                                                    aria-label="<?= $direccion === 'subir' ? 'Mover antes' : 'Mover después' ?> la foto <?= $posicion + 1 ?>">
                                                <i class="bi <?= e($icono) ?>" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                    <form method="post" action="<?= e(url('/admin/equipos/fotos/eliminar')) ?>"
                                          data-confirmar="¿Eliminar esta foto? Se borra también el archivo.">
                                        <?= csrfCampo() ?>
                                        <input type="hidden" name="idFotografia" value="<?= e($foto['idFotografia']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Eliminar" aria-label="Eliminar la foto <?= $posicion + 1 ?>">
                                            <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php // enctype="multipart/form-data" es obligatorio para enviar archivos. ?>
                <?php if (!$soloConsulta): ?>
                <form method="post" action="<?= e(url('/admin/equipos/fotos/subir')) ?>" enctype="multipart/form-data" class="subir-fotos">
                    <?= csrfCampo() ?>
                    <input type="hidden" name="idEquipo" value="<?= e($idEquipo) ?>">
                    <label for="fotos" class="form-label">Agregar fotos</label>
                    <div class="subir-fotos-campos">
                        <input type="file" id="fotos" name="fotos[]" multiple required class="form-control"
                               accept="image/jpeg,image/png,image/webp" aria-describedby="ayudaFotos">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload" aria-hidden="true"></i> Subir</button>
                    </div>
                    <div class="form-text" id="ayudaFotos">JPG, PNG o WebP · hasta 2 MB cada una · máximo <?= FotografiaEquipo::MAXIMO_POR_ENVIO ?> a la vez.</div>
                </form>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<section class="panel mb-4" id="tarifas">
    <div class="panel-cabecera">
        <h3 class="panel-titulo">Tarifas</h3>
        <?php if ($activo && !$soloConsulta): ?>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTarifa">
                <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Nueva tarifa
            </button>
        <?php endif; ?>
    </div>
    <div class="panel-cuerpo">
        <?php if (!$tarifas): ?>
            <div class="estado-vacio">
                <span class="estado-vacio-icono"><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
                <p class="estado-vacio-titulo">Sin tarifas</p>
                <p class="estado-vacio-texto">Sin una tarifa por día vigente, el equipo no muestra precio en el catálogo.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table tabla-er">
                    <thead>
                    <tr>
                        <th scope="col">Periodo</th>
                        <th scope="col" class="text-end">Monto</th>
                        <th scope="col" class="text-end d-none d-sm-table-cell">Depósito sugerido</th>
                        <th scope="col">Desde</th>
                        <th scope="col">Hasta</th>
                        <th scope="col">Situación</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tarifas as $tarifa): ?>
                        <tr>
                            <td class="texto-fuerte"><?= e(Tarifa::PERIODOS[$tarifa['tipoPeriodo']] ?? $tarifa['tipoPeriodo']) ?></td>
                            <td class="text-end"><?= dinero($tarifa['monto']) ?></td>
                            <td class="text-end d-none d-sm-table-cell"><?= dinero($tarifa['depositoSugerido']) ?></td>
                            <td><?= e(fechaCorta($tarifa['fechaInicioVigencia'])) ?></td>
                            <td><?= $tarifa['fechaFinVigencia'] === null ? '<span class="texto-suave">Sin fecha final</span>' : e(fechaCorta($tarifa['fechaFinVigencia'])) ?></td>
                            <td><?= estadoBadge($tarifa['situacion']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="texto-suave small mt-3 mb-0">
                El catálogo usa la tarifa <strong>por día</strong> vigente. Para cambiar un precio, crea una tarifa nueva:
                la anterior del mismo periodo se cierra un día antes.
            </p>
        <?php endif; ?>
    </div>
</section>

<section class="panel" id="historial">
    <div class="panel-cabecera">
        <h3 class="panel-titulo">Historial de estados</h3>
        <span class="texto-suave small">Bitácora automática: cada cambio queda con fecha, usuario y motivo.</span>
    </div>
    <div class="panel-cuerpo">
        <div class="table-responsive">
            <table class="table tabla-er">
                <thead>
                <tr>
                    <th scope="col">Fecha</th>
                    <th scope="col">Cambio</th>
                    <th scope="col">Motivo</th>
                    <th scope="col" class="d-none d-md-table-cell">Usuario</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($historial as $registro): ?>
                    <tr>
                        <td class="text-nowrap"><?= e(fechaCorta($registro['fechaCambio'], true)) ?></td>
                        <td class="text-nowrap">
                            <?php if ($registro['estadoAnterior'] !== null && $registro['estadoAnterior'] !== $registro['estadoNuevo']): ?>
                                <?= estadoBadge($registro['estadoAnterior']) ?> <i class="bi bi-arrow-right texto-suave" aria-label="a"></i>
                            <?php endif; ?>
                            <?= estadoBadge($registro['estadoNuevo']) ?>
                        </td>
                        <td><?= e($registro['motivo']) ?></td>
                        <td class="d-none d-md-table-cell"><?= e($registro['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php
// ---------------------------------------------------------------------
// Modales (solo el Administrador los usa)
// ---------------------------------------------------------------------
if ($soloConsulta) {
    return;
}

// Editar (el mismo modal del listado, pero vuelve al detalle).
$origen = 'detalle';
$volver = '';
require RAIZ . '/app/views/equipos/_formulario.php';

$erroresEstado = $formularioEstado['errores'] ?? [];
$erroresBaja = $formularioBaja['errores'] ?? [];
$erroresTarifa = $formularioTarifa['errores'] ?? [];
$valorTarifa = function (string $campo, string $porDefecto = '') use ($formularioTarifa): string {
    return (string) ($formularioTarifa['valores'][$campo] ?? $porDefecto);
};
?>

<?php // Nueva tarifa: cierra la abierta del mismo periodo (Tarifa::crear, en una transacción). ?>
<div class="modal fade modal-er" id="modalTarifa" tabindex="-1" aria-labelledby="modalTarifaTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formularioTarifa ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= e(url('/admin/equipos/tarifas/guardar')) ?>" class="modal-content" novalidate>
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-cash-coin"></i></span>
                <div>
                    <h2 class="modal-title" id="modalTarifaTitulo">Nueva tarifa</h2>
                    <p class="modal-subtitulo"><?= e($equipo['codigoInventario']) ?> · <?= e($equipo['nombre']) ?></p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?= csrfCampo() ?>
                <input type="hidden" name="idEquipo" value="<?= e($idEquipo) ?>">
                <div>
                    <label for="tipoPeriodo" class="form-label"><i class="bi bi-calendar-range" aria-hidden="true"></i> Periodo</label>
                    <select id="tipoPeriodo" name="tipoPeriodo" required class="form-select<?= isset($erroresTarifa['tipoPeriodo']) ? ' is-invalid' : '' ?>">
                        <?php foreach (Tarifa::PERIODOS as $periodo => $textoPeriodo): ?>
                            <option value="<?= e($periodo) ?>"<?= $valorTarifa('tipoPeriodo', 'Dia') === $periodo ? ' selected' : '' ?>><?= e($textoPeriodo) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erroresTarifa['tipoPeriodo'])): ?>
                        <div class="invalid-feedback"><?= e($erroresTarifa['tipoPeriodo']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="montoTarifa" class="form-label"><i class="bi bi-currency-dollar" aria-hidden="true"></i> Monto</label>
                        <input type="number" id="montoTarifa" name="monto" min="0.01" step="0.01" required
                               class="form-control<?= isset($erroresTarifa['monto']) ? ' is-invalid' : '' ?>"
                               placeholder="Ej. 120.00" value="<?= e($valorTarifa('monto')) ?>">
                        <?php if (isset($erroresTarifa['monto'])): ?>
                            <div class="invalid-feedback"><?= e($erroresTarifa['monto']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6">
                        <label for="depositoTarifa" class="form-label"><i class="bi bi-safe" aria-hidden="true"></i> Depósito sugerido</label>
                        <input type="number" id="depositoTarifa" name="depositoSugerido" min="0" step="0.01"
                               class="form-control<?= isset($erroresTarifa['depositoSugerido']) ? ' is-invalid' : '' ?>"
                               placeholder="Ej. 300.00" value="<?= e($valorTarifa('depositoSugerido')) ?>">
                        <?php if (isset($erroresTarifa['depositoSugerido'])): ?>
                            <div class="invalid-feedback"><?= e($erroresTarifa['depositoSugerido']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <label for="inicioTarifa" class="form-label"><i class="bi bi-calendar-event" aria-hidden="true"></i> Vigente desde</label>
                    <input type="date" id="inicioTarifa" name="fechaInicioVigencia" required
                           class="form-control<?= isset($erroresTarifa['fechaInicioVigencia']) ? ' is-invalid' : '' ?>"
                           value="<?= e($valorTarifa('fechaInicioVigencia', date('Y-m-d'))) ?>">
                    <?php if (isset($erroresTarifa['fechaInicioVigencia'])): ?>
                        <div class="invalid-feedback"><?= e($erroresTarifa['fechaInicioVigencia']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Si ya hay una tarifa del mismo periodo sin fecha final, se cierra el día anterior.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php // Cambiar estado: solo los estados manuales; el motivo queda en la bitácora. ?>
<div class="modal fade modal-er" id="modalEstado" tabindex="-1" aria-labelledby="modalEstadoTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formularioEstado ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= e(url('/admin/equipos/cambiar-estado')) ?>" class="modal-content" novalidate>
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-activity"></i></span>
                <div>
                    <h2 class="modal-title" id="modalEstadoTitulo">Cambiar estado</h2>
                    <p class="modal-subtitulo"><?= e($equipo['codigoInventario']) ?> · ahora <?= e(Equipo::ESTADOS[$equipo['estado']]) ?></p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?= csrfCampo() ?>
                <input type="hidden" name="idEquipo" value="<?= e($idEquipo) ?>">
                <div>
                    <label for="estadoNuevo" class="form-label"><i class="bi bi-activity" aria-hidden="true"></i> Nuevo estado</label>
                    <select id="estadoNuevo" name="estado" required class="form-select<?= isset($erroresEstado['estado']) ? ' is-invalid' : '' ?>">
                        <option value="">Elige el nuevo estado</option>
                        <?php foreach (Equipo::ESTADOS_MANUALES as $valorEstado): ?>
                            <?php if ($valorEstado !== $equipo['estado']): ?>
                                <option value="<?= e($valorEstado) ?>"<?= ($formularioEstado['valores']['estado'] ?? '') === $valorEstado ? ' selected' : '' ?>>
                                    <?= e(Equipo::ESTADOS[$valorEstado]) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erroresEstado['estado'])): ?>
                        <div class="invalid-feedback"><?= e($erroresEstado['estado']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">"Alquilado" no se elige aquí: lo pone el módulo de Operación.</div>
                </div>
                <div>
                    <label for="motivoEstado" class="form-label"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Motivo</label>
                    <textarea id="motivoEstado" name="motivo" rows="3" maxlength="300" required
                              class="form-control<?= isset($erroresEstado['motivo']) ? ' is-invalid' : '' ?>"
                              placeholder="Ej. Revisión de frenos después de la devolución"><?= e($formularioEstado['valores']['motivo'] ?? '') ?></textarea>
                    <?php if (isset($erroresEstado['motivo'])): ?>
                        <div class="invalid-feedback"><?= e($erroresEstado['motivo']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Cambiar</button>
            </div>
        </form>
    </div>
</div>

<?php // Baja (desactivar): Figma "Formulario Desactivar Equipo" (53:1052). ?>
<div class="modal fade modal-er modal-er-peligro" id="modalBaja" tabindex="-1" aria-labelledby="modalBajaTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formularioBaja ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= e(url('/admin/equipos/cambiar-activo')) ?>" class="modal-content" novalidate>
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-slash-circle"></i></span>
                <div>
                    <h2 class="modal-title" id="modalBajaTitulo">Desactivar equipo</h2>
                    <p class="modal-subtitulo">Baja de activos operativos</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?= csrfCampo() ?>
                <input type="hidden" name="idEquipo" value="<?= e($idEquipo) ?>">
                <input type="hidden" name="activar" value="0">

                <?php if ($contratoAbierto !== null): ?>
                    <div class="alert alert-danger mb-0" role="alert">
                        <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                        <span>No se puede desactivar: el equipo está en el contrato
                            <strong><?= e($contratoAbierto['numeroContrato']) ?></strong> (<?= e($contratoAbierto['estado']) ?>).
                            Espera a que termine o quítalo de ese contrato primero.</span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger mb-0" role="note">
                        <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                        <span>El equipo desactivado quedará <strong>Fuera de servicio</strong> y no estará disponible
                            para alquileres ni entregas. La baja queda en el historial con fecha y usuario.</span>
                    </div>
                <?php endif; ?>

                <div>
                    <p class="form-label mb-1"><i class="bi bi-box-seam" aria-hidden="true"></i> Equipo a desactivar</p>
                    <p class="mb-0"><span class="codigo"><?= e($equipo['codigoInventario']) ?></span> <?= e($equipo['nombre']) ?></p>
                </div>
                <div>
                    <label for="motivoBaja" class="form-label"><i class="bi bi-shield-exclamation" aria-hidden="true"></i> Motivo de la baja</label>
                    <select id="motivoBaja" name="motivo" required class="form-select<?= isset($erroresBaja['motivo']) ? ' is-invalid' : '' ?>">
                        <option value="">Elige el motivo</option>
                        <?php foreach (Equipo::MOTIVOS_BAJA as $motivoBaja): ?>
                            <option value="<?= e($motivoBaja) ?>"<?= ($formularioBaja['valores']['motivo'] ?? '') === $motivoBaja ? ' selected' : '' ?>><?= e($motivoBaja) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erroresBaja['motivo'])): ?>
                        <div class="invalid-feedback"><?= e($erroresBaja['motivo']) ?></div>
                    <?php endif; ?>
                </div>
                <?php // Solo se muestra si el motivo es "Otro" (data-mostrar-si, ver app.js). ?>
                <div data-mostrar-si="motivo=Otro"<?= ($formularioBaja['valores']['motivo'] ?? '') === 'Otro' ? '' : ' hidden' ?>>
                    <label for="detalleBaja" class="form-label"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Describe el motivo</label>
                    <textarea id="detalleBaja" name="detalle" rows="2" maxlength="250"
                              class="form-control<?= isset($erroresBaja['detalle']) ? ' is-invalid' : '' ?>"
                              placeholder="Ej. Donado a la municipalidad"><?= e($formularioBaja['valores']['detalle'] ?? '') ?></textarea>
                    <?php if (isset($erroresBaja['detalle'])): ?>
                        <div class="invalid-feedback"><?= e($erroresBaja['detalle']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger"<?= $contratoAbierto !== null ? ' disabled' : '' ?>>
                    <i class="bi bi-slash-circle" aria-hidden="true"></i> Desactivar
                </button>
            </div>
        </form>
    </div>
</div>
