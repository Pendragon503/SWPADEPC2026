<h1 class="h3 mb-1">Hola, <?= e(Auth::usuario()['nombres']) ?></h1>
<p class="text-secondary mb-4">Mi panel · <?= e(Auth::nombreCompleto()) ?> · cliente n.º <?= e(Auth::idCliente()) ?></p>

<div class="alert alert-info">
    Panel de prueba. Aquí irán el catálogo, la disponibilidad, la solicitud de alquiler,
    "Mis alquileres" y tu perfil.
</div>
