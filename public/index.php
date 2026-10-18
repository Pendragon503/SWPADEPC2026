<?php
/**
 * EquipRent - Front controller. Todas las peticiones entran por aquí.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router(config('app.urlBase'));
require RAIZ . '/app/rutas.php';

try {
    $router->despachar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (ErrorHttp $e) {
    (new ErrorController())->mostrar($e->getCode());
}
