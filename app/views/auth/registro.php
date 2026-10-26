<?php
/**
 * Registro de clientes ("Crear una cuenta" del login), con el estilo del login.
 * Mismos datos y validaciones que Clientes (Cliente::validar), sin las observaciones
 * internas; la contraseña la elige el cliente.
 *
 * @var array $valores  lo escrito en el último envío con errores (sin contraseñas)
 * @var array $errores
 */
$valor = function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
// [campo, etiqueta, icono, tipo, largo, ejemplo, opcional, autocomplete]
$campos = [
    ['nombres', 'Nombres', 'bi-person-fill', 'text', 80, 'Ej. Juan Carlos', false, 'given-name'],
    ['apellidos', 'Apellidos', 'bi-person-fill', 'text', 80, 'Ej. Pérez López', false, 'family-name'],
    ['documentoIdentidad', 'Documento de identidad', 'bi-person-vcard-fill', 'text', 25, 'Ej. 01234567-8', false, 'off'],
    ['correo', 'Correo electrónico', 'bi-envelope-fill', 'email', 120, 'ejemplo@correo.com', false, 'email'],
    ['telefono', 'Teléfono', 'bi-telephone-fill', 'tel', 20, 'Ej. 7000-0000', true, 'tel'],
    ['direccion', 'Dirección', 'bi-geo-alt-fill', 'text', 250, 'Ej. Colonia Escalón, San Salvador', true, 'street-address'],
];
?>
<section class="tarjeta-acceso tarjeta-registro mx-auto">
    <div class="tarjeta-acceso-cabecera">
        <span class="cabecera-franja" aria-hidden="true"></span>
        <span class="cabecera-cinta" aria-hidden="true"></span>
        <span class="logo-cuadro" aria-hidden="true"><i class="bi bi-person-plus-fill"></i></span>
        <h1 class="titulo-acceso">Crear una cuenta</h1>
        <p class="subtitulo-acceso">Regístrate para alquilar equipos</p>
    </div>

    <div class="tarjeta-acceso-cuerpo">
        <?php require RAIZ . '/app/views/auth/_mensajes.php'; ?>

        <form method="post" action="<?= e(url('/registro')) ?>" class="formulario-acceso" novalidate data-enviando="Creando la cuenta…">
            <?= csrfCampo() ?>
            <div class="row g-3">
                <?php foreach ($campos as [$campo, $etiqueta, $icono, $tipo, $largo, $ejemplo, $opcional, $autocompletar]): ?>
                    <div class="col-sm-6">
                        <label for="<?= e($campo) ?>" class="etiqueta-acceso"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i> <?= e($etiqueta) ?><?= $opcional ? ' <span class="etiqueta-opcional">(opcional)</span>' : '' ?></label>
                        <input type="<?= e($tipo) ?>" id="<?= e($campo) ?>" name="<?= e($campo) ?>" maxlength="<?= e($largo) ?>" autocomplete="<?= e($autocompletar) ?>"
                               class="form-control control-acceso<?= isset($errores[$campo]) ? ' is-invalid' : '' ?>" placeholder="<?= e($ejemplo) ?>" value="<?= e($valor($campo)) ?>">
                        <?php if (isset($errores[$campo])): ?><div class="invalid-feedback"><?= e($errores[$campo]) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="col-sm-6">
                    <label for="tipoPersona" class="etiqueta-acceso"><i class="bi bi-building-fill" aria-hidden="true"></i> Tipo de cliente</label>
                    <select id="tipoPersona" name="tipoPersona" class="form-select control-acceso<?= isset($errores['tipoPersona']) ? ' is-invalid' : '' ?>">
                        <?php foreach (Cliente::TIPOS as $tipo => $texto): ?>
                            <option value="<?= e($tipo) ?>"<?= $tipo === ($valor('tipoPersona') ?: 'Natural') ? ' selected' : '' ?>><?= e($texto) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php // Razón social y NIT solo para empresas (data-mostrar-si de app.js). ?>
                <div class="col-sm-6" data-mostrar-si="tipoPersona=Juridica"<?= $valor('tipoPersona') === 'Juridica' ? '' : ' hidden' ?>>
                    <label for="razonSocial" class="etiqueta-acceso"><i class="bi bi-briefcase-fill" aria-hidden="true"></i> Razón social</label>
                    <input type="text" id="razonSocial" name="razonSocial" maxlength="150" class="form-control control-acceso<?= isset($errores['razonSocial']) ? ' is-invalid' : '' ?>"
                           placeholder="Ej. Constructora El Roble S.A. de C.V." value="<?= e($valor('razonSocial')) ?>">
                    <?php if (isset($errores['razonSocial'])): ?><div class="invalid-feedback"><?= e($errores['razonSocial']) ?></div><?php endif; ?>
                </div>
                <div class="col-sm-6" data-mostrar-si="tipoPersona=Juridica"<?= $valor('tipoPersona') === 'Juridica' ? '' : ' hidden' ?>>
                    <label for="documentoTributario" class="etiqueta-acceso"><i class="bi bi-receipt" aria-hidden="true"></i> NIT de la empresa</label>
                    <input type="text" id="documentoTributario" name="documentoTributario" maxlength="25" class="form-control control-acceso<?= isset($errores['documentoTributario']) ? ' is-invalid' : '' ?>"
                           placeholder="Ej. 0614-150820-102-1" value="<?= e($valor('documentoTributario')) ?>">
                    <?php if (isset($errores['documentoTributario'])): ?><div class="invalid-feedback"><?= e($errores['documentoTributario']) ?></div><?php endif; ?>
                </div>

                <div class="col-sm-6">
                    <label for="clave" class="etiqueta-acceso"><i class="bi bi-lock-fill" aria-hidden="true"></i> Contraseña</label>
                    <div class="position-relative">
                        <input type="password" id="clave" name="clave" autocomplete="new-password" placeholder="Mínimo 8, con letras y números"
                               class="form-control control-acceso control-clave<?= isset($errores['clave']) ? ' is-invalid' : '' ?>">
                        <button type="button" class="boton-ver-clave" data-ver-clave="#clave" aria-label="Mostrar contraseña" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        <?php if (isset($errores['clave'])): ?><div class="invalid-feedback"><?= e($errores['clave']) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label for="confirmacion" class="etiqueta-acceso"><i class="bi bi-lock-fill" aria-hidden="true"></i> Confirmar contraseña</label>
                    <input type="password" id="confirmacion" name="confirmacion" autocomplete="new-password" placeholder="Escríbela otra vez"
                           class="form-control control-acceso<?= isset($errores['confirmacion']) ? ' is-invalid' : '' ?>">
                    <?php if (isset($errores['confirmacion'])): ?><div class="invalid-feedback"><?= e($errores['confirmacion']) ?></div><?php endif; ?>
                </div>
            </div>
            <button type="submit" class="boton-acceso mt-2"><i class="bi bi-person-check-fill" aria-hidden="true"></i> Crear mi cuenta</button>
        </form>
        <p class="pie-acceso">¿Ya tienes cuenta? <a href="<?= e(url('/login')) ?>">Inicia sesión</a></p>
    </div>
</section>
