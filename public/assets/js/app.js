/* EquipRent - JavaScript propio (Bootstrap 5 + jQuery). */
$(function () {
    // Los enlaces de prueba (href="#") de módulos que aún no existen no navegan.
    $(document).on('click', 'a[href="#"]', function (evento) {
        evento.preventDefault();
    });

    // Confirmación antes de enviar: <form data-confirmar="¿Seguro que...?">
    $(document).on('submit', 'form[data-confirmar]', function (evento) {
        if (!window.confirm($(this).data('confirmar'))) {
            evento.preventDefault();
        }
    });

    // Evitar envíos dobles (RF-15): <form data-enviando="Enviando…">
    // Al enviar, desactiva sus botones y cambia el texto del botón de envío.
    // Va DESPUÉS de data-confirmar: si el usuario canceló la confirmación, no hace nada.
    // (El servidor también se protege: ver Solicitud::enviar.)
    $(document).on('submit', 'form[data-enviando]', function (evento) {
        if (evento.isDefaultPrevented()) {
            return;
        }
        var $formulario = $(this);
        $formulario.find('button[type=submit]').prop('disabled', true)
            .first().html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> '
                + $('<span>').text($formulario.data('enviando')).html());
    });

    // ---------------------------------------------------------------------
    // Modales con formulario (.modal-er). Un mismo modal sirve para crear y editar.
    // El botón que lo abre lleva:
    //   data-accion="URL a la que se envía el formulario"
    //   data-titulo="Crear categoría" / "Editar categoría"
    //   data-campos='{"nombre": "...", ...}'  valor de cada campo, por su name
    // Ejemplo completo en app/views/categorias/index.php.
    // ---------------------------------------------------------------------
    $(document).on('show.bs.modal', '.modal-er', function (evento) {
        var $boton = $(evento.relatedTarget);
        if ($boton.length === 0) {
            // Se abrió solo porque hubo errores: se deja como lo dibujó el servidor.
            return;
        }

        var $modal = $(this);
        var $formulario = $modal.find('form');
        $formulario.attr('action', $boton.data('accion'));
        $modal.find('.modal-title').text($boton.data('titulo'));

        // jQuery convierte el JSON de data-campos en un objeto.
        $.each($boton.data('campos') || {}, function (nombre, valor) {
            $formulario.find('[name="' + nombre + '"]').val(valor);
        });

        // Opcional: data-modo="crear" | "editar" (ejemplo: app/views/equipos/_formulario.php)
        //   [data-bloquear-al-editar] queda en solo lectura al editar (por ejemplo, el código)
        //   [data-solo-al-editar]     solo se muestra al editar (por ejemplo, el estado actual)
        //   [data-solo-al-crear]      solo se muestra al crear (por ejemplo, un aviso)
        var editando = $boton.data('modo') === 'editar';
        $formulario.find('[data-bloquear-al-editar]').prop('readOnly', editando);
        $formulario.find('[data-solo-al-editar]').prop('hidden', !editando);
        $formulario.find('[data-solo-al-crear]').prop('hidden', editando);

        // Los bloques data-mostrar-si dependen de los valores recién copiados.
        actualizarMostrarSi($formulario);

        // Quitar los errores de un envío anterior.
        $formulario.find('.is-invalid').removeClass('is-invalid');
        $formulario.find('.invalid-feedback').remove();
    });

    // Al terminar de abrirse: foco en el primer campo con error, o en el primer campo que se puede escribir.
    $(document).on('shown.bs.modal', '.modal-er', function () {
        var $campo = $(this).find('.is-invalid').first();
        if ($campo.length === 0) {
            $campo = $(this).find('input:not([type=hidden]):not([readonly]), textarea, select').first();
        }
        $campo.trigger('focus');
    });

    // Mostrar un bloque solo cuando otro campo del mismo formulario tiene cierto valor:
    //   <div data-mostrar-si="motivo=Otro"> se ve solo si el campo name="motivo" vale "Otro".
    // (Ejemplo en app/views/equipos/detalle.php, modal de baja.)
    function actualizarMostrarSi($formulario) {
        $formulario.find('[data-mostrar-si]').each(function () {
            var partes = String($(this).data('mostrar-si')).split('=');
            var valorActual = $formulario.find('[name="' + partes[0] + '"]').val();
            $(this).prop('hidden', valorActual !== partes[1]);
        });
    }
    $(document).on('change', 'form :input', function () {
        actualizarMostrarSi($(this).closest('form'));
    });

    // El servidor marca con data-abrir-al-cargar="1" el modal que debe abrirse
    // al cargar la página (cuando el último envío tuvo errores de validación).
    $('.modal[data-abrir-al-cargar="1"]').each(function () {
        bootstrap.Modal.getOrCreateInstance(this).show();
    });

    // Copiar al portapapeles el texto de otro elemento: <button data-copiar="#idDelElemento">
    // (por ejemplo, la contraseña temporal). El botón cambia a "Copiado".
    $(document).on('click', '[data-copiar]', function () {
        var $boton = $(this);
        var texto = $($boton.data('copiar')).text().trim();
        var listo = function () {
            $boton.html('<i class="bi bi-check2" aria-hidden="true"></i> Copiado');
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texto).then(listo);
        } else {
            // Sin HTTPS no hay navigator.clipboard: se copia desde un campo temporal.
            var $campo = $('<textarea>').val(texto).appendTo('body').trigger('select');
            document.execCommand('copy');
            $campo.remove();
            listo();
        }
    });

    // Botón de imprimir (reportes): <button data-imprimir>. Lo que no sale en papel lleva .no-imprimir.
    $(document).on('click', '[data-imprimir]', function () {
        window.print();
    });

    // Mostrar u ocultar la contraseña: <button data-ver-clave="#idDelInput">
    $('[data-ver-clave]').on('click', function () {
        var $boton = $(this);
        var $campo = $($boton.data('ver-clave'));
        var mostrar = $campo.attr('type') === 'password';

        $campo.attr('type', mostrar ? 'text' : 'password');
        $boton.attr({
            'aria-pressed': mostrar ? 'true' : 'false',
            'aria-label': mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña'
        });
        $boton.find('.bi').toggleClass('bi-eye', !mostrar).toggleClass('bi-eye-slash', mostrar);
    });
});
