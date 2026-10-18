<?php
/**
 * Inicio de sesión (RF-01). Diseño: Figma "Sistema Web", frame "Login".
 * Los mensajes flash se muestran aquí, dentro de la tarjeta; así el layout ya no los repite.
 */
$iconosAlerta = [
    'danger'  => 'bi-exclamation-circle-fill',
    'warning' => 'bi-exclamation-triangle-fill',
    'success' => 'bi-check-circle-fill',
    'info'    => 'bi-info-circle-fill',
];
?>
<section class="tarjeta-acceso mx-auto">
    <div class="tarjeta-acceso-cabecera">
        <span class="cabecera-franja" aria-hidden="true"></span>
        <span class="cabecera-cinta" aria-hidden="true"></span>
        <span class="logo-cuadro" aria-hidden="true"><i class="bi bi-cone-striped"></i></span>
        <h1 class="titulo-acceso">Iniciar sesión</h1>
        <p class="subtitulo-acceso">Sistema de Alquiler de Equipos</p>
    </div>

    <div class="tarjeta-acceso-cuerpo">
        <p class="intro-acceso">Accede para gestionar tus alquileres y equipos</p>

        <?php foreach (flashes() as $mensaje): ?>
            <div class="alert alert-<?= e($mensaje['tipo']) ?> alerta-acceso" role="alert">
                <i class="bi <?= e($iconosAlerta[$mensaje['tipo']] ?? 'bi-info-circle-fill') ?>" aria-hidden="true"></i>
                <span><?= e($mensaje['mensaje']) ?></span>
            </div>
        <?php endforeach; ?>

        <form method="post" action="<?= e(url('/login')) ?>" class="formulario-acceso" novalidate>
            <?= csrfCampo() ?>
            <div class="campo-acceso">
                <label for="correo" class="etiqueta-acceso"><i class="bi bi-envelope-fill" aria-hidden="true"></i> Correo electrónico</label>
                <input type="email" class="form-control control-acceso" id="correo" name="correo" required autofocus
                       autocomplete="username" placeholder="ejemplo@correo.com" value="<?= e(valorAnterior('correo')) ?>">
            </div>
            <div class="campo-acceso">
                <label for="clave" class="etiqueta-acceso"><i class="bi bi-lock-fill" aria-hidden="true"></i> Contraseña</label>
                <div class="position-relative">
                    <input type="password" class="form-control control-acceso control-clave" id="clave" name="clave" required
                           autocomplete="current-password" placeholder="Ingresa tu contraseña">
                    <button type="button" class="boton-ver-clave" data-ver-clave="#clave"
                            aria-label="Mostrar contraseña" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="boton-acceso">
                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Iniciar sesión
            </button>
        </form>
    </div>
</section>
