<?php
/**
 * EquipRent - Controlador base.
 */
declare(strict_types=1);

abstract class Controller
{
    /**
     * Muestra app/views/{$vista}.php dentro de app/views/layouts/{$layout}.php.
     * Dentro de la vista, cada clave de $datos es una variable.
     */
    protected function view(string $vista, array $datos = [], string $layout = 'principal'): void
    {
        $contenido = $this->renderizar("app/views/$vista.php", $datos);
        echo $this->renderizar("app/views/layouts/$layout.php", $datos + ['contenido' => $contenido]);
    }

    protected function redirect(string $ruta): void
    {
        header('Location: ' . url($ruta));
        exit;
    }

    /**
     * Exige sesión iniciada y uno de los roles indicados.
     * Sin sesión redirige al login; con otro rol, responde 403.
     */
    protected function requireRole(string ...$roles): void
    {
        if (!Auth::estaAutenticado()) {
            flash('warning', 'Inicia sesión para continuar.');
            $this->redirect('/login');
        }
        if (!in_array(Auth::rol(), $roles, true)) {
            throw new ErrorHttp('Acceso denegado', 403);
        }
    }

    private function renderizar(string $archivo, array $datos): string
    {
        extract($datos, EXTR_SKIP);
        ob_start();
        try {
            require RAIZ . '/' . $archivo;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }
}
