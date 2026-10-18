<?php
/**
 * EquipRent - Router simple: método + ruta -> 'Controlador@metodo'.
 * Todas las peticiones POST deben traer un token CSRF válido.
 */
declare(strict_types=1);

class Router
{
    private array $rutas = ['GET' => [], 'POST' => []];
    private string $rutaBase;

    public function __construct(string $urlBase)
    {
        $this->rutaBase = rtrim(parse_url($urlBase, PHP_URL_PATH) ?: '', '/');
    }

    public function get(string $ruta, string $accion): void
    {
        $this->rutas['GET'][$this->normalizar($ruta)] = $accion;
    }

    public function post(string $ruta, string $accion): void
    {
        $this->rutas['POST'][$this->normalizar($ruta)] = $accion;
    }

    public function despachar(string $metodo, string $uri): void
    {
        $metodo = strtoupper($metodo);
        $ruta = $this->rutaRelativa($uri);
        $accion = $this->rutas[$metodo][$ruta] ?? null;

        if ($accion === null) {
            throw new ErrorHttp('Página no encontrada', 404);
        }

        if ($metodo === 'POST' && !csrfValido($_POST['_csrf'] ?? null)) {
            flash('warning', 'El formulario expiró. Inténtalo de nuevo.');
            header('Location: ' . url('/'));
            exit;
        }

        [$controlador, $metodoAccion] = explode('@', $accion, 2);
        if (!class_exists($controlador) || !method_exists($controlador, $metodoAccion)) {
            throw new LogicException("La ruta $metodo $ruta apunta a $accion, que no existe.");
        }

        (new $controlador())->$metodoAccion();
    }

    /**
     * Quita la URL base y la cadena de consulta: /SWPADEPC2026/public/admin?x=1 -> /admin
     */
    private function rutaRelativa(string $uri): string
    {
        $ruta = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');
        if ($this->rutaBase !== '' && stripos($ruta, $this->rutaBase) === 0) {
            $ruta = substr($ruta, strlen($this->rutaBase));
        }
        if (strcasecmp($ruta, '/index.php') === 0) {
            $ruta = '/';
        }
        return $this->normalizar($ruta);
    }

    private function normalizar(string $ruta): string
    {
        return '/' . trim($ruta, '/');
    }
}
