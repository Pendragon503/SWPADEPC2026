<?php
/**
 * Layout de Administrador y Operador: menú lateral oscuro + barra superior naranja.
 * En pantallas pequeñas el menú lateral se abre como panel deslizable (offcanvas).
 *
 * @var string $titulo
 * @var string $contenido
 */
$usuario = Auth::usuario();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'EquipRent') ?> · EquipRent</title>
<?php require RAIZ . '/app/views/layouts/recursos.php'; ?>
</head>
<body class="app-er">
<div class="app-marco">
    <aside class="offcanvas-lg offcanvas-start barra-lateral" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralTitulo">
        <div class="barra-lateral-interior">
            <div class="franja-precaucion" aria-hidden="true"></div>
            <div class="barra-lateral-marca">
                <span class="marca-icono marca-icono-38" aria-hidden="true"><i class="bi bi-cone-striped"></i></span>
                <div class="flex-grow-1">
                    <span class="barra-lateral-nombre" id="menuLateralTitulo">EQUIP<span>RENT</span></span>
                    <span class="barra-lateral-subtitulo">Panel de control</span>
                </div>
                <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas"
                        data-bs-target="#menuLateral" aria-label="Cerrar menú"></button>
            </div>
            <?php require RAIZ . '/app/views/layouts/menu.php'; ?>
        </div>
    </aside>

    <div class="app-principal">
        <header class="barra-superior">
            <button type="button" class="boton-menu d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#menuLateral"
                    aria-controls="menuLateral" aria-label="Abrir menú">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <h1 class="barra-superior-titulo"><?= e($titulo ?? 'EquipRent') ?></h1>

            <div class="barra-superior-usuario">
                <a class="pildora-usuario" href="<?= e(url('/perfil')) ?>" title="Mi perfil">
                    <span class="avatar-iniciales" aria-hidden="true"><?= e(iniciales($usuario['nombres'], $usuario['apellidos'])) ?></span>
                    <span class="pildora-usuario-texto">
                        <strong><?= e(Auth::nombreCompleto()) ?></strong>
                        <small><?= e(Auth::rol()) ?></small>
                    </span>
                </a>
                <form method="post" action="<?= e(url('/logout')) ?>" class="m-0">
                    <?= csrfCampo() ?>
                    <button type="submit" class="boton-salir" aria-label="Cerrar sesión">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Salir</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="app-contenido">
            <?php require RAIZ . '/app/views/layouts/mensajes.php'; ?>
            <?= $contenido ?>
        </main>
    </div>
</div>
<?php require RAIZ . '/app/views/layouts/scripts.php'; ?>
</body>
</html>
