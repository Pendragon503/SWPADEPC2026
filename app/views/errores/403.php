<div class="text-center mx-auto contenido-angosto">
    <p class="display-1 fw-bold text-warning mb-0">403</p>
    <h1 class="h4">Acceso denegado</h1>
    <p class="text-secondary">No tienes permiso para ver esta página.</p>
    <a href="<?= e(url('/')) ?>" class="btn btn-dark"><?= Auth::estaAutenticado() ? 'Ir a mi panel' : 'Iniciar sesión' ?></a>
</div>
