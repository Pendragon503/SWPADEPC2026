<?php
/**
 * Modal de crear / editar cliente (componente .modal-er, ancho y en dos columnas):
 * a la izquierda los datos del usuario (tabla usuario), a la derecha los del cliente.
 *
 * Variables que define clientes/index.php: $formulario (o null) y $volver.
 * La razón social solo se muestra si el tipo es "Jurídica" (data-mostrar-si, ver app.js).
 */
$modoFormulario = $formulario['modo'] ?? 'crear';
$valoresFormulario = $formulario['valores'] ?? ['tipoPersona' => 'Natural'];
$erroresFormulario = $formulario['errores'] ?? [];

$valor = function (string $campo) use ($valoresFormulario): string {
    return (string) ($valoresFormulario[$campo] ?? '');
};
$claseError = function (string $campo) use ($erroresFormulario): string {
    return isset($erroresFormulario[$campo]) ? ' is-invalid' : '';
};
$mensajeError = function (string $campo) use ($erroresFormulario): string {
    return isset($erroresFormulario[$campo])
        ? '<div class="invalid-feedback">' . e($erroresFormulario[$campo]) . '</div>'
        : '';
};

// [campo, etiqueta, icono, tipo de input, largo máximo, placeholder, opcional, autocomplete]
$camposUsuario = [
    ['nombres', 'Nombres', 'bi-person', 'text', 80, 'Ej. Juan Carlos', false, 'given-name'],
    ['apellidos', 'Apellidos', 'bi-person', 'text', 80, 'Ej. Pérez López', false, 'family-name'],
    ['documentoIdentidad', 'Documento de identidad', 'bi-person-vcard', 'text', 25, 'Ej. 01234567-8', false, 'off'],
    ['correo', 'Correo (usuario del portal)', 'bi-envelope', 'email', 120, 'Ej. compras@empresa.com', false, 'off'],
    ['telefono', 'Teléfono', 'bi-telephone', 'tel', 20, 'Ej. 2222-0000', true, 'off'],
    ['direccion', 'Dirección', 'bi-geo-alt', 'text', 250, 'Ej. Colonia Escalón, San Salvador', true, 'off'],
];
$camposContacto = [
    ['contactoAlterno', 'Contacto alterno', 'bi-person-lines-fill', 'text', 120, 'Ej. Pedro Ramírez', true, 'off'],
    ['telefonoAlterno', 'Teléfono alterno', 'bi-telephone-plus', 'tel', 20, 'Ej. 7222-3333', true, 'off'],
];

// Dibuja un campo de texto con su etiqueta y su error (para no repetir el mismo HTML 10 veces).
$campoTexto = function (array $definicion) use ($valor, $claseError, $mensajeError): string {
    [$campo, $etiqueta, $icono, $tipo, $largo, $ejemplo, $opcional, $autocompletar] = $definicion;
    return '<label for="cli_' . e($campo) . '" class="form-label"><i class="bi ' . e($icono) . '" aria-hidden="true"></i> '
        . e($etiqueta) . ($opcional ? ' <span class="etiqueta-opcional">(opcional)</span>' : '') . '</label>'
        . '<input type="' . e($tipo) . '" id="cli_' . e($campo) . '" name="' . e($campo) . '" maxlength="' . e($largo) . '"'
        . ' autocomplete="' . e($autocompletar) . '"' . ($opcional ? '' : ' required')
        . ' class="form-control' . $claseError($campo) . '" placeholder="' . e($ejemplo) . '" value="' . e($valor($campo)) . '">'
        . $mensajeError($campo);
};
?>
<div class="modal fade modal-er" id="modalCliente" tabindex="-1" aria-labelledby="modalClienteTitulo" aria-hidden="true"
     data-abrir-al-cargar="<?= $formulario ? '1' : '0' ?>">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="post" action="<?= e(url($modoFormulario === 'editar' ? '/clientes/actualizar' : '/clientes/guardar')) ?>"
              class="modal-content" novalidate>
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <h2 class="modal-title" id="modalClienteTitulo"><?= $modoFormulario === 'editar' ? 'Editar cliente' : 'Registrar cliente' ?></h2>
                    <p class="modal-subtitulo">Datos del cliente y de su usuario</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <?= csrfCampo() ?>
                <input type="hidden" name="idCliente" value="<?= e($formulario['idCliente'] ?? '') ?>">
                <input type="hidden" name="volver" value="<?= e($volver) ?>">

                <div class="row g-4">
                    <div class="col-md-6">
                        <p class="titulo-formulario">Persona o contacto principal</p>
                        <div class="d-grid gap-3">
                            <?php foreach ($camposUsuario as $definicion): ?>
                                <div><?= $campoTexto($definicion) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <p class="titulo-formulario">Datos del cliente</p>
                        <div class="d-grid gap-3">
                            <div>
                                <label for="cli_tipoPersona" class="form-label"><i class="bi bi-building" aria-hidden="true"></i> Tipo de persona</label>
                                <select id="cli_tipoPersona" name="tipoPersona" required class="form-select<?= $claseError('tipoPersona') ?>">
                                    <?php foreach (Cliente::TIPOS as $tipo => $textoTipo): ?>
                                        <option value="<?= e($tipo) ?>"<?= $valor('tipoPersona') === $tipo ? ' selected' : '' ?>><?= e($textoTipo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $mensajeError('tipoPersona') ?>
                            </div>
                            <?php // Solo para personas jurídicas (empresas). ?>
                            <div data-mostrar-si="tipoPersona=Juridica"<?= $valor('tipoPersona') === 'Juridica' ? '' : ' hidden' ?>>
                                <label for="cli_razonSocial" class="form-label"><i class="bi bi-briefcase" aria-hidden="true"></i> Razón social</label>
                                <input type="text" id="cli_razonSocial" name="razonSocial" maxlength="150"
                                       class="form-control<?= $claseError('razonSocial') ?>" placeholder="Ej. Constructora del Valle S.A. de C.V."
                                       value="<?= e($valor('razonSocial')) ?>">
                                <?= $mensajeError('razonSocial') ?>
                            </div>
                            <div>
                                <label for="cli_documentoTributario" class="form-label"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Documento tributario (NIT)
                                    <span class="etiqueta-opcional">(obligatorio si es jurídica)</span></label>
                                <input type="text" id="cli_documentoTributario" name="documentoTributario" maxlength="25"
                                       class="form-control<?= $claseError('documentoTributario') ?>" placeholder="Ej. 0614-150820-102-1"
                                       value="<?= e($valor('documentoTributario')) ?>">
                                <?= $mensajeError('documentoTributario') ?>
                            </div>
                            <?php foreach ($camposContacto as $definicion): ?>
                                <div><?= $campoTexto($definicion) ?></div>
                            <?php endforeach; ?>
                            <div>
                                <label for="cli_observaciones" class="form-label"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Observaciones <span class="etiqueta-opcional">(opcional)</span></label>
                                <textarea id="cli_observaciones" name="observaciones" rows="2" maxlength="500"
                                          class="form-control<?= $claseError('observaciones') ?>"
                                          placeholder="Ej. Paga con transferencia; pedir orden de compra"><?= e($valor('observaciones')) ?></textarea>
                                <?= $mensajeError('observaciones') ?>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="form-text mb-0" data-solo-al-crear<?= $modoFormulario === 'editar' ? ' hidden' : '' ?>>
                    <i class="bi bi-key" aria-hidden="true"></i> Al guardar se crea su usuario con una contraseña temporal,
                    que se muestra una sola vez para entregársela al cliente.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>
