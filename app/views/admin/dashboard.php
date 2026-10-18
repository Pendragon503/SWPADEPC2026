<h1 class="h3 mb-1">Hola, <?= e(Auth::usuario()['nombres']) ?></h1>
<p class="text-secondary mb-4">Panel del administrador · <?= e(Auth::nombreCompleto()) ?></p>

<div class="alert alert-info">
    Panel de prueba. Aquí irán los indicadores (RF-13) y el acceso a usuarios, inventario,
    categorías, tarifas, clientes, contratos, configuración y reportes.
</div>
