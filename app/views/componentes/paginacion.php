<?php
/**
 * Paginación reutilizable (Bootstrap). Antes de incluirla, la vista define:
 *
 *   $rutaPaginacion       ruta del listado, por ejemplo '/admin/categorias'
 *   $parametrosPaginacion filtros que se conservan al cambiar de página, por ejemplo ['buscar' => $buscar]
 *   $pagina, $totalPaginas
 *
 * Muestra "Anterior", hasta 5 números alrededor de la página actual y "Siguiente".
 * Si hay una sola página no imprime nada.
 */
if ($totalPaginas <= 1) {
    return;
}

// URL de una página, con los filtros que no estén vacíos.
$urlPagina = function (int $numero) use ($rutaPaginacion, $parametrosPaginacion): string {
    $parametros = array_filter($parametrosPaginacion, function ($valor) {
        return $valor !== '' && $valor !== null;
    });
    if ($numero > 1) {
        $parametros['pagina'] = $numero;
    }
    return url($rutaPaginacion) . ($parametros ? '?' . http_build_query($parametros) : '');
};

$desde = max(1, $pagina - 2);
$hasta = min($totalPaginas, $pagina + 2);
?>
<nav aria-label="Páginas">
    <ul class="pagination">
        <?php if ($pagina <= 1): ?>
            <li class="page-item disabled"><span class="page-link" aria-hidden="true"><i class="bi bi-chevron-left"></i></span></li>
        <?php else: ?>
            <li class="page-item">
                <a class="page-link" href="<?= e($urlPagina($pagina - 1)) ?>" aria-label="Página anterior">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </a>
            </li>
        <?php endif; ?>
        <?php for ($numero = $desde; $numero <= $hasta; $numero++): ?>
            <li class="page-item<?= $numero === $pagina ? ' active' : '' ?>">
                <a class="page-link" href="<?= e($urlPagina($numero)) ?>"<?= $numero === $pagina ? ' aria-current="page"' : '' ?>><?= e($numero) ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($pagina >= $totalPaginas): ?>
            <li class="page-item disabled"><span class="page-link" aria-hidden="true"><i class="bi bi-chevron-right"></i></span></li>
        <?php else: ?>
            <li class="page-item">
                <a class="page-link" href="<?= e($urlPagina($pagina + 1)) ?>" aria-label="Página siguiente">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
