<?php
/**
 * Detalle de un equipo para el cliente (Figma "Detalle - Daniel"): fotos, precio,
 * especificaciones, condiciones de uso, tarifas vigentes y calendario de ocupación.
 *
 * @var array $equipo           Catalogo::equipo()
 * @var array $fotos            FotografiaEquipo::listarPorEquipo()
 * @var array $especificaciones especificaciones(): [[etiqueta, valor], ...]
 * @var array $tarifas          tarifas vigentes (día, semana, mes)
 * @var array $ocupadas         Catalogo::fechasOcupadas()
 * @var array $calendario       calendarioMes()
 * @var array $solicitud        Solicitud::actual(): sus fechas llenan el formulario
 */
$fotoPrincipal = $fotos[0] ?? null;
?>
<div class="contenedor-cliente contenido-cliente">
    <a href="<?= e(url('/cliente/catalogo')) ?>" class="enlace-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al catálogo</a>

    <section class="panel mb-4">
        <div class="panel-cuerpo">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="detalle-foto-principal">
                        <?= imagenEquipo($fotoPrincipal['urlFotografia'] ?? $equipo['fotoPrincipal'], $equipo['nombre'], iconoCategoria($equipo['categoria'])) ?>
                    </div>
                    <?php if (count($fotos) > 1): ?>
                        <ul class="detalle-miniaturas">
                            <?php foreach (array_slice($fotos, 1) as $foto): ?>
                                <li class="detalle-miniatura"><?= imagenEquipo($foto['urlFotografia'], $foto['textoAlternativo'] ?? $equipo['nombre']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="col-lg-6">
                    <p class="tarjeta-equipo-etiqueta mt-0">Categoría: <?= e($equipo['categoria']) ?></p>
                    <h1 class="detalle-equipo-nombre"><?= e($equipo['nombre']) ?></h1>
                    <p class="detalle-equipo-precio"><?= dinero($equipo['precioDiario']) ?> <span>/día</span></p>
                    <p class="texto-suave small mb-0">
                        <?= e(trim(($equipo['marca'] ?? '') . ' ' . ($equipo['modelo'] ?? ''))) ?>
                        · Depósito sugerido <?= dinero($equipo['depositoSugerido']) ?>
                    </p>

                    <?php if ($tarifas): ?>
                        <ul class="detalle-tarifas">
                            <?php foreach ($tarifas as $tarifa): ?>
                                <li><?= e(Tarifa::PERIODOS[$tarifa['tipoPeriodo']]) ?>: <strong><?= dinero($tarifa['monto']) ?></strong></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="form-text mt-1">La solicitud se calcula con la tarifa por día.</p>
                    <?php endif; ?>

                    <?php if ($especificaciones): ?>
                        <h2 class="detalle-subtitulo">Especificaciones técnicas</h2>
                        <ul class="detalle-especificaciones">
                            <?php foreach ($especificaciones as [$etiqueta, $valor]): ?>
                                <li><?php if ($etiqueta !== ''): ?><strong><?= e($etiqueta) ?>:</strong> <?php endif; ?><?= e($valor) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php // Agregar a "Mi solicitud": las fechas se llenan con las de la solicitud, si ya tiene. ?>
                    <form method="post" action="<?= e(url('/cliente/solicitud/agregar')) ?>" class="detalle-acciones" id="solicitar" novalidate>
                        <?= csrfCampo() ?>
                        <input type="hidden" name="idEquipo" value="<?= e($equipo['idEquipo']) ?>">
                        <input type="hidden" name="volver" value="<?= e('/cliente/equipo?id=' . $equipo['idEquipo']) ?>">
                        <div class="detalle-fechas">
                            <div>
                                <label for="inicio" class="form-label">Desde</label>
                                <input type="date" id="inicio" name="inicio" min="<?= e(date('Y-m-d')) ?>" required class="form-control" value="<?= e($solicitud['inicio'] ?? '') ?>">
                            </div>
                            <div>
                                <label for="fin" class="form-label">Hasta</label>
                                <input type="date" id="fin" name="fin" min="<?= e(date('Y-m-d')) ?>" required class="form-control" value="<?= e($solicitud['fin'] ?? '') ?>">
                            </div>
                        </div>
                        <a href="#calendario" class="btn btn-claro"><i class="bi bi-calendar3" aria-hidden="true"></i> Ver disponibilidad</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-cart-plus" aria-hidden="true"></i> Solicitar alquiler</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-6">
            <section class="panel">
                <div class="panel-cabecera panel-cabecera-linea">
                    <h2 class="panel-titulo panel-titulo-mayus">Descripción y condiciones de uso</h2>
                </div>
                <div class="panel-cuerpo">
                    <dl class="ficha-textos mt-0 pt-0 border-0">
                        <div><dt>Descripción</dt><dd><?= $equipo['descripcion'] ? nl2br(e($equipo['descripcion'])) : '—' ?></dd></div>
                        <div><dt>Condiciones de uso</dt><dd><?= $equipo['condicionesUso'] ? nl2br(e($equipo['condicionesUso'])) : '—' ?></dd></div>
                    </dl>
                </div>
            </section>
        </div>
        <div class="col-lg-6" id="calendario">
            <section class="panel">
                <div class="panel-cabecera panel-cabecera-linea">
                    <h2 class="panel-titulo panel-titulo-mayus">Disponibilidad</h2>
                </div>
                <div class="panel-cuerpo">
                    <?php $urlMes = url('/cliente/equipo?id=' . $equipo['idEquipo']); require RAIZ . '/app/views/componentes/calendario.php'; ?>
                    <?php if ($ocupadas): ?>
                        <p class="texto-suave small mt-3 mb-1">Fechas ocupadas:</p>
                        <ul class="lista-ocupadas">
                            <?php foreach ($ocupadas as $rango): ?>
                                <li><?= $rango['hasta'] === null
                                        ? 'Desde el ' . e(fechaCorta($rango['desde'])) . ' hasta que se devuelva'
                                        : e(periodo($rango['desde'], $rango['hasta'])) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="texto-suave small mt-3 mb-0">No tiene reservas: está libre en cualquier fecha desde hoy.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
