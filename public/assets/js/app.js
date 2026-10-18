/* EquipRent - JavaScript propio (Bootstrap 5 + jQuery). */
$(function () {
    // Los enlaces de módulos aún no implementados no navegan.
    $('.menu-lateral a[href="#"]').on('click', function (evento) {
        evento.preventDefault();
    });
});
