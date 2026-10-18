/* EquipRent - JavaScript propio (Bootstrap 5 + jQuery). */
$(function () {
    // Los enlaces de módulos aún no implementados no navegan.
    $('.menu-lateral a[href="#"]').on('click', function (evento) {
        evento.preventDefault();
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
