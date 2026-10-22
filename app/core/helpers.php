<?php
/**
 * EquipRent - Funciones de ayuda: configuración, URLs, escape de salida,
 * sesión, mensajes flash, token CSRF y manejo de errores.
 */
declare(strict_types=1);

/**
 * Lee un valor de config/config.local.php con notación de puntos: config('bd.host').
 */
function config(string $clave, $defecto = null)
{
    static $config = null;
    if ($config === null) {
        $config = require RAIZ . '/config/config.local.php';
    }

    $valor = $config;
    foreach (explode('.', $clave) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return $defecto;
        }
        $valor = $valor[$parte];
    }
    return $valor;
}

/**
 * Escapa texto para mostrarlo en HTML.
 */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL absoluta dentro de la aplicación: url('/login').
 */
function url(string $ruta = '/'): string
{
    return rtrim(config('app.urlBase'), '/') . '/' . ltrim($ruta, '/');
}

/**
 * URL de un archivo dentro de public/assets.
 */
function asset(string $ruta): string
{
    return url('assets/' . ltrim($ruta, '/'));
}

// ---------------------------------------------------------------------
// Presentación
// ---------------------------------------------------------------------

/**
 * Etiqueta de color para un estado guardado en la base (equipo, contrato,
 * daño, mantenimiento, activo/inactivo): estadoBadge('FueraServicio')
 * imprime una píldora roja que dice "Fuera de servicio".
 */
function estadoBadge(string $estado): string
{
    // estado en la base => [color, texto que ve el usuario]
    $estados = [
        // equipo
        'Disponible'        => ['verde', 'Disponible'],
        'Alquilado'         => ['naranja', 'Alquilado'],
        'Revision'          => ['gris', 'En revisión'],
        'Mantenimiento'     => ['azul', 'Mantenimiento'],
        'FueraServicio'     => ['rojo', 'Fuera de servicio'],
        // contrato
        'Pendiente'         => ['naranja', 'Pendiente'],
        'Aprobado'          => ['azul', 'Aprobado'],
        'Rechazado'         => ['rojo', 'Rechazado'],
        'Activo'            => ['verde', 'Activo'],
        'Finalizado'        => ['gris', 'Finalizado'],
        'Cancelado'         => ['gris', 'Cancelado'],
        // daño
        'Reportado'         => ['naranja', 'Reportado'],
        'EnEvaluacion'      => ['azul', 'En evaluación'],
        'EnReparacion'      => ['azul', 'En reparación'],
        'Resuelto'          => ['verde', 'Resuelto'],
        'Descartado'        => ['gris', 'Descartado'],
        // mantenimiento
        'Diagnostico'       => ['azul', 'Diagnóstico'],
        'EsperandoRepuesto' => ['naranja', 'Esperando repuesto'],
        // registros que se activan o desactivan (categorías, usuarios...)
        'Activa'            => ['verde', 'Activa'],
        'Inactiva'          => ['gris', 'Inactiva'],
        'Inactivo'          => ['gris', 'Inactivo'],
    ];

    [$color, $texto] = $estados[$estado] ?? ['gris', $estado];
    return '<span class="estado estado-' . $color . '">' . e($texto) . '</span>';
}

/**
 * Iniciales para el avatar: iniciales('Carlos Andrés', 'Rodríguez') -> 'CR'.
 */
function iniciales(string $nombres, string $apellidos): string
{
    $primera = mb_substr(trim($nombres), 0, 1);
    $segunda = mb_substr(trim($apellidos), 0, 1);
    return mb_strtoupper($primera . $segunda);
}

// ---------------------------------------------------------------------
// Sesión
// ---------------------------------------------------------------------

function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $rutaBase = parse_url(config('app.urlBase'), PHP_URL_PATH) ?: '/';
    session_name('EQUIPRENT');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => rtrim($rutaBase, '/') . '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/**
 * Cierra la sesión por completo (datos, cookie e identificador).
 */
function destruirSesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'],
        ]);
    }
    session_destroy();
}

// ---------------------------------------------------------------------
// Mensajes flash (se muestran una sola vez)
// Tipos: success, danger, warning, info (clases de alerta de Bootstrap)
// ---------------------------------------------------------------------

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flashes(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

/**
 * Valor que el usuario escribió antes de una redirección (para volver a llenar el formulario).
 */
function valorAnterior(string $campo, string $defecto = ''): string
{
    $valor = $_SESSION['valoresAnteriores'][$campo] ?? $defecto;
    unset($_SESSION['valoresAnteriores'][$campo]);
    return (string) $valor;
}

function guardarValoresAnteriores(array $valores): void
{
    $_SESSION['valoresAnteriores'] = $valores;
}

// ---------------------------------------------------------------------
// CSRF: todo formulario POST imprime csrfCampo(); el Router lo verifica.
// ---------------------------------------------------------------------

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCampo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrfToken()) . '">';
}

function csrfValido(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// ---------------------------------------------------------------------
// Errores
// ---------------------------------------------------------------------

/**
 * ¿La base rechazó el INSERT/UPDATE por un valor repetido en una columna UNIQUE?
 * (error 1062 de MySQL). Con $restriccion se comprueba cuál: el mensaje de MySQL
 * incluye el nombre del UNIQUE, por ejemplo 'uq_equipo_serie'.
 */
function esValorDuplicado(PDOException $e, string $restriccion = ''): bool
{
    if (!isset($e->errorInfo[1]) || (int) $e->errorInfo[1] !== 1062) {
        return false;
    }
    return $restriccion === '' || stripos((string) ($e->errorInfo[2] ?? ''), $restriccion) !== false;
}

/**
 * Último recurso para cualquier excepción no atrapada.
 * Con debug muestra el detalle; sin debug, una página genérica.
 */
function manejarExcepcion(Throwable $e): void
{
    error_log(sprintf('[EquipRent] %s: %s en %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    try {
        (new ErrorController())->errorInterno($e);
    } catch (Throwable $otro) {
        echo 'Ocurrió un error.';
    }
}
