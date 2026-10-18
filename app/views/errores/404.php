<div class="text-center mx-auto contenido-angosto">
    <p class="display-1 fw-bold text-warning mb-0">404</p>
    <h1 class="h4">Página no encontrada</h1>
    <p class="text-secondary">La dirección que buscas no existe.</p>
    <a href="<?= e(url('/')) ?>" class="btn btn-dark"><?= Auth::estaAutenticado() ? 'Ir a mi panel' : 'Iniciar sesión' ?></a>
</div>
