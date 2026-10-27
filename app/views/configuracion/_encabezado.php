<?php
/**
 * Encabezado de Configuración del sistema con sus 4 pestañas. Lo incluyen las vistas de
 * Categorías, Desactivación de equipos, Ver reportes y Usuarios y roles.
 * Antes de incluirlo, la vista define $pestana.
 */
?>
<div class="encabezado-pagina">
    <div>
        <h2 class="encabezado-pagina-titulo">Configuración del sistema</h2>
        <p class="encabezado-pagina-texto">Catálogo de categorías, equipos dados de baja, reportes y usuarios del personal.</p>
    </div>
</div>
<?php $grupo = 'configuracion'; require RAIZ . '/app/views/componentes/pestanas.php'; ?>
