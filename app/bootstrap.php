<?php
/**
 * EquipRent - Arranque de la aplicación: configuración, autocarga,
 * manejo de errores y sesión.
 */
declare(strict_types=1);

define('RAIZ', dirname(__DIR__));

if (!is_file(RAIZ . '/config/config.local.php')) {
    http_response_code(500);
    exit('Falta config/config.local.php. Copia config/config.example.php con ese nombre y completa los datos.');
}

require RAIZ . '/app/core/helpers.php';

// Autocarga: las clases viven en core/, controllers/ o models/ con el mismo nombre de archivo.
spl_autoload_register(function (string $clase): void {
    foreach (['core', 'controllers', 'models'] as $carpeta) {
        $archivo = RAIZ . "/app/$carpeta/$clase.php";
        if (is_file($archivo)) {
            require $archivo;
            return;
        }
    }
});

date_default_timezone_set('America/El_Salvador');

ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('zend.exception_ignore_args', '1');
error_reporting(E_ALL);
set_error_handler(function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
    if (!(error_reporting() & $nivel)) {
        return false;
    }
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});
set_exception_handler('manejarExcepcion');
limpiarParametrosUrl(); // ?buscar[]=x se ignora en lugar de dar un error

iniciarSesion();
Auth::verificarVigencia(); // un usuario o rol desactivado pierde la sesión en su siguiente clic
