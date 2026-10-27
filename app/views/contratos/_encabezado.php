<?php
/**
 * Encabezado de Alquileres con sus pestañas (Todos | Solicitudes pendientes).
 * Antes de incluirlo, la vista define $pestana ('todos' | 'solicitudes') y $textoPestana.
 */
?>
<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo">Alquileres</h2>
        <p class="encabezado-pagina-texto"><?= e($textoPestana) ?></p>
    </div>
    <a href="<?= e(url('/contratos/nuevo')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo alquiler</a>
</div>
<?php $grupo = 'alquileres'; require RAIZ . '/app/views/componentes/pestanas.php'; ?>
