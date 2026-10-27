<?php
/**
 * Mi perfil (los tres roles): ficha de solo lectura (rol, código, tipo), formulario de
 * datos y cambio de contraseña. Se ve con el layout del cliente o con el principal.
 *
 * @var bool  $esCliente
 * @var array $perfil    datos actuales (Cliente::buscarPorId o Usuario::buscarPorId)
 * @var array $valores   lo escrito en el último envío con errores, o los datos actuales
 * @var array $errores
 */
// [campo, etiqueta, tipo, largo, opcional]
$campos = [
    ['nombres', 'Nombres', 'text', 80, false], ['apellidos', 'Apellidos', 'text', 80, false],
    ['documentoIdentidad', 'Documento de identidad', 'text', 25, false], ['correo', 'Correo (usuario)', 'email', 120, false],
    ['telefono', 'Teléfono', 'tel', 20, true], ['direccion', 'Dirección', 'text', 250, true],
];
if ($esCliente) {
    if ($perfil['tipoPersona'] === 'Juridica') {
        array_push($campos, ['razonSocial', 'Razón social', 'text', 150, false], ['documentoTributario', 'NIT de la empresa', 'text', 25, false]);
    }
    array_push($campos, ['contactoAlterno', 'Contacto alterno', 'text', 120, true], ['telefonoAlterno', 'Teléfono alterno', 'tel', 20, true]);
}
$campoClave = function (string $campo, string $etiqueta, string $autocompletar) use ($errores): string {
    return '<div><label for="' . $campo . '" class="form-label">' . e($etiqueta) . '</label>'
        . '<input type="password" id="' . $campo . '" name="' . $campo . '" autocomplete="' . $autocompletar . '"'
        . ' class="form-control' . (isset($errores[$campo]) ? ' is-invalid' : '') . '">'
        . (isset($errores[$campo]) ? '<div class="invalid-feedback">' . e($errores[$campo]) . '</div>' : '') . '</div>';
};
?>
<div class="<?= $esCliente ? 'contenedor-cliente contenido-cliente' : '' ?>">
    <div class="encabezado-pagina">
        <div>
            <h2 class="encabezado-pagina-titulo">Mi perfil</h2>
            <p class="encabezado-pagina-texto">Tus datos de contacto y tu contraseña.</p>
        </div>
    </div>

    <section class="panel mb-4">
        <div class="panel-cuerpo">
            <dl class="ficha-datos">
                <div><dt>Rol</dt><dd><?= e(Auth::rol()) ?></dd></div>
                <?php if ($esCliente): ?>
                    <div><dt>Código de cliente</dt><dd><span class="codigo"><?= e($perfil['codigoCliente']) ?></span></dd></div>
                    <div><dt>Tipo</dt><dd><?= e(Cliente::TIPOS[$perfil['tipoPersona']]) ?></dd></div>
                <?php endif; ?>
                <div><dt>Último acceso</dt><dd><?= e(fechaCorta($perfil['ultimoAcceso'], true)) ?></dd></div>
            </dl>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="panel">
                <div class="panel-cabecera panel-cabecera-linea"><h3 class="panel-titulo panel-titulo-mayus">Mis datos</h3></div>
                <div class="panel-cuerpo">
                    <form method="post" action="<?= e(url('/perfil/datos')) ?>" class="row g-3" novalidate data-enviando="Guardando…">
                        <?= csrfCampo() ?>
                        <?php foreach ($campos as [$campo, $etiqueta, $tipo, $largo, $opcional]): ?>
                            <div class="col-md-6">
                                <label for="<?= e($campo) ?>" class="form-label"><?= e($etiqueta) ?><?= $opcional ? ' <span class="etiqueta-opcional">(opcional)</span>' : '' ?></label>
                                <input type="<?= e($tipo) ?>" id="<?= e($campo) ?>" name="<?= e($campo) ?>" maxlength="<?= e($largo) ?>"
                                       class="form-control<?= isset($errores[$campo]) ? ' is-invalid' : '' ?>" value="<?= e($valores[$campo] ?? '') ?>">
                                <?php if (isset($errores[$campo])): ?><div class="invalid-feedback"><?= e($errores[$campo]) ?></div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Guardar mis datos</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="panel">
                <div class="panel-cabecera panel-cabecera-linea"><h3 class="panel-titulo panel-titulo-mayus">Cambiar contraseña</h3></div>
                <div class="panel-cuerpo">
                    <form method="post" action="<?= e(url('/perfil/clave')) ?>" class="d-grid gap-3" novalidate data-enviando="Cambiando…">
                        <?= csrfCampo() ?>
                        <?= $campoClave('claveActual', 'Contraseña actual', 'current-password') ?>
                        <?= $campoClave('clave', 'Nueva contraseña', 'new-password') ?>
                        <?= $campoClave('confirmacion', 'Confirmar la nueva', 'new-password') ?>
                        <p class="form-text m-0">Mínimo 8 caracteres, con letras y números.</p>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-key-fill" aria-hidden="true"></i> Cambiar contraseña</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
