<h1 class="h3 mb-1">Hola, <?= e(Auth::usuario()['nombres']) ?></h1>
<p class="text-secondary mb-4">Panel del operador · <?= e(Auth::nombreCompleto()) ?></p>

<div class="alert alert-info">
    Panel de prueba. Aquí irán las solicitudes, entregas, devoluciones, daños y mantenimiento.
</div>
