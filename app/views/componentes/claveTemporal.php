<?php
/**
 * Cuadro de la contraseña temporal (.modal-er), una sola vez: la toma de la sesión con
 * tomarClaveTemporal(), que la borra. Lo incluye el layout principal; se abre solo
 * (data-abrir-al-cargar) y solo se cierra con la X o con "Ya la anoté"
 * (data-bs-backdrop="static" y data-bs-keyboard="false"). "Copiar": data-copiar de app.js.
 */
$claveTemporal = tomarClaveTemporal();
if ($claveTemporal === null) {
    return;
}
?>
<div class="modal fade modal-er" id="modalClaveTemporal" tabindex="-1" aria-labelledby="modalClaveTemporalTitulo" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false" data-abrir-al-cargar="1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-key-fill"></i></span>
                <div>
                    <h2 class="modal-title" id="modalClaveTemporalTitulo">Contraseña temporal</h2>
                    <p class="modal-subtitulo">Entrégasela a la persona</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div>
                    <p class="texto-fuerte mb-0"><?= e($claveTemporal['nombre']) ?></p>
                    <p class="texto-suave mb-0"><?= e($claveTemporal['correo']) ?></p>
                </div>
                <div class="clave-temporal">
                    <code id="claveTemporalValor"><?= e($claveTemporal['clave']) ?></code>
                    <button type="button" class="btn btn-secondary" data-copiar="#claveTemporalValor">
                        <i class="bi bi-clipboard" aria-hidden="true"></i> Copiar
                    </button>
                </div>
                <div class="alert alert-warning mb-0" role="note">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    <span>Anota o copia esta contraseña. No se volverá a mostrar.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Ya la anoté</button>
            </div>
        </div>
    </div>
</div>
