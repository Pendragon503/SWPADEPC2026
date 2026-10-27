<?php
/**
 * Registro de clientes ("Crear una cuenta" del login). Diseño: Figma "Registro":
 * tarjeta del login, campos con ícono naranja y botón "Registrarse". Siempre crea
 * una persona Natural; las empresas las registra el personal en Clientes.
 *
 * @var array $valores  lo escrito en el último envío con errores (sin contraseñas)
 * @var array $errores
 */
// El nombre completo se separa en nombres y apellidos (Usuario::separarNombre):
// sus errores de largo también se muestran bajo este campo.
$errores['nombreCompleto'] = $errores['nombreCompleto'] ?? $errores['nombres'] ?? $errores['apellidos'] ?? null;

// [campo, etiqueta, icono, tipo, ejemplo, autocomplete, largo]
$campos = [
    ['nombreCompleto', 'Nombre completo', 'bi-person-fill', 'text', 'Ej. Juan Armando Pérez Almada', 'name', 161],
    ['documentoIdentidad', 'Documento de identidad (DUI)', 'bi-person-vcard-fill', 'text', 'Ej. 01234567-8', 'off', 25],
    ['correo', 'Correo electrónico', 'bi-envelope-fill', 'email', 'ejemplo@correo.com', 'email', 120],
    ['clave', 'Contraseña', 'bi-lock-fill', 'password', 'Mínimo 8 caracteres', 'new-password', 72],
    ['confirmacion', 'Confirmar contraseña', 'bi-shield-lock-fill', 'password', 'Repite tu contraseña', 'new-password', 72],
];
?>
<section class="tarjeta-acceso mx-auto">
    <div class="tarjeta-acceso-cabecera">
        <span class="cabecera-franja" aria-hidden="true"></span>
        <span class="cabecera-cinta" aria-hidden="true"></span>
        <span class="logo-cuadro" aria-hidden="true"><i class="bi bi-person-plus-fill"></i></span>
        <h1 class="titulo-acceso">Crear cuenta</h1>
        <p class="subtitulo-acceso subtitulo-acceso-naranja">Sistema de Alquiler de Equipos</p>
    </div>

    <div class="tarjeta-acceso-cuerpo">
        <?php require RAIZ . '/app/views/auth/_mensajes.php'; ?>

        <form method="post" action="<?= e(url('/registro')) ?>" class="formulario-acceso" novalidate data-enviando="Registrando…">
            <?= csrfCampo() ?>
            <?php foreach ($campos as [$campo, $etiqueta, $icono, $tipo, $ejemplo, $autocompletar, $largo]): ?>
                <?php $error = $errores[$campo] ?? null; ?>
                <div class="campo-acceso">
                    <label for="<?= e($campo) ?>" class="etiqueta-acceso"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i> <?= e($etiqueta) ?></label>
                    <div class="position-relative">
                        <input type="<?= e($tipo) ?>" id="<?= e($campo) ?>" name="<?= e($campo) ?>" maxlength="<?= e($largo) ?>" autocomplete="<?= e($autocompletar) ?>"
                               class="form-control control-acceso<?= $campo === 'clave' ? ' control-clave' : '' ?><?= $error ? ' is-invalid' : '' ?>"
                               placeholder="<?= e($ejemplo) ?>" value="<?= $tipo === 'password' ? '' : e($valores[$campo] ?? '') ?>">
                        <?php if ($campo === 'clave'): ?>
                            <button type="button" class="boton-ver-clave" data-ver-clave="#clave" aria-label="Mostrar contraseña" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        <?php endif; ?>
                    </div>
                    <?php // Fuera del contenedor relativo para que el botón del ojo no se mueva (d-block: ya no es hermano del input). ?>
                    <?php if ($error): ?><div class="invalid-feedback d-block"><?= e($error) ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <p class="form-text mt-0">La contraseña debe tener al menos 8 caracteres, con letras y números.</p>
            <button type="submit" class="boton-acceso"><i class="bi bi-person-check-fill" aria-hidden="true"></i> Registrarse</button>
        </form>
        <p class="pie-acceso">¿Ya tienes cuenta activa? <a href="<?= e(url('/login')) ?>">Iniciar sesión</a></p>
        <p class="pie-acceso nota-empresa"><i class="bi bi-building" aria-hidden="true"></i> ¿Eres una empresa? Solicita tu registro a nuestro personal.</p>
    </div>
</section>
