<?php
/**
 * EquipRent - Páginas de error. No usan la base de datos, así que
 * funcionan aunque MySQL esté apagado.
 */
declare(strict_types=1);

class ErrorController extends Controller
{
    private const TITULOS = [
        403 => 'Acceso denegado',
        404 => 'Página no encontrada',
    ];

    /** 404, 403 u otro código HTTP controlado. */
    public function mostrar(int $codigo): void
    {
        $codigo = isset(self::TITULOS[$codigo]) ? $codigo : 404;
        http_response_code($codigo);
        $this->view("errores/$codigo", ['titulo' => self::TITULOS[$codigo]], 'simple');
    }

    /** 500: con debug muestra el detalle; sin debug, un mensaje genérico. */
    public function errorInterno(Throwable $e): void
    {
        $this->view('errores/500', [
            'titulo'    => 'Ocurrió un error',
            'excepcion' => config('app.debug') ? $e : null,
        ], 'simple');
    }
}
