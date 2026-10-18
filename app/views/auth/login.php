<div class="card shadow-sm mx-auto contenido-angosto">
    <div class="card-body p-4">
        <h1 class="h4 mb-4">Iniciar sesión</h1>
        <form method="post" action="<?= e(url('/login')) ?>" novalidate>
            <?= csrfCampo() ?>
            <div class="mb-3">
                <label for="correo" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" required autofocus
                       autocomplete="username" value="<?= e(valorAnterior('correo')) ?>">
            </div>
            <div class="mb-4">
                <label for="clave" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="clave" name="clave" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-warning w-100 fw-semibold">Entrar</button>
        </form>
    </div>
</div>
