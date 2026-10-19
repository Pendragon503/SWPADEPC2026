<?php
/**
 * EquipRent - Módulo de Categorías (Administrador).
 *
 * MÓDULO DE EJEMPLO: copiarlo para crear otros módulos (ver docs/GUIA_DESARROLLO.md).
 *
 * Rutas (app/rutas.php):
 *   GET  /admin/categorias                 -> index          listado con búsqueda y paginación
 *   POST /admin/categorias/guardar         -> guardar        crear (formulario del modal)
 *   POST /admin/categorias/actualizar      -> actualizar     editar (mismo modal)
 *   POST /admin/categorias/cambiar-estado  -> cambiarEstado  activar / desactivar
 *
 * El Router no tiene parámetros en la URL (/categorias/5 no existe): en los POST
 * el id viaja en un campo oculto, y en las páginas GET de detalle se usa ?id=5.
 *
 * Patrón POST -> redirect -> GET: después de guardar, el controlador SIEMPRE
 * redirige al listado. Así, si el usuario recarga la página (F5), el navegador
 * no vuelve a enviar el formulario ni crea la categoría dos veces.
 */
declare(strict_types=1);

class CategoriaController extends Controller
{
    /** Filas por página del listado. */
    private const POR_PAGINA = 10;

    /** Clave de sesión donde se guarda el formulario que tuvo errores. */
    private const SESION_FORMULARIO = 'formularioCategoria';

    public function __construct()
    {
        // Todas las acciones de este controlador son solo para el Administrador:
        // sin sesión redirige al login; con otro rol responde 403.
        $this->requireRole('Administrador');
    }

    /**
     * GET /admin/categorias?buscar=texto&pagina=2
     */
    public function index(): void
    {
        // 1. Leer los filtros de la URL (siempre con un valor por defecto).
        $buscar = trim((string) ($_GET['buscar'] ?? ''));
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));

        // 2. Paginación: total de filas -> total de páginas -> filas de esta página.
        $total = Categoria::contar($buscar);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }
        $desplazamiento = ($pagina - 1) * self::POR_PAGINA;
        $categorias = Categoria::listar($buscar, self::POR_PAGINA, $desplazamiento);

        // 3. Si el último guardado tuvo errores, guardar() o actualizar() dejaron en la
        //    sesión lo que el usuario escribió. Se lee UNA vez y se borra, igual que un flash.
        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);

        $this->view('categorias/index', [
            'titulo'         => 'Categorías',
            'menuActivo'     => 'Categorías',
            'categorias'     => $categorias,
            'buscar'         => $buscar,
            'pagina'         => $pagina,
            'totalPaginas'   => $totalPaginas,
            'total'          => $total,
            'desplazamiento' => $desplazamiento,
            'formulario'     => $formulario,
        ]);
    }

    /**
     * POST /admin/categorias/guardar
     * (El token CSRF ya lo verificó el Router antes de llegar aquí.)
     */
    public function guardar(): void
    {
        // 1. Leer y limpiar lo que llegó del formulario.
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));

        // 2. Validar en el servidor. La validación del navegador (required, maxlength)
        //    ayuda, pero se puede saltar: la que cuenta es esta.
        $errores = $this->validar($nombre, $descripcion, 0);
        if ($errores) {
            $this->volverConErrores('crear', 0, $nombre, $descripcion, $errores);
        }

        // 3. Guardar.
        try {
            Categoria::crear($nombre, $descripcion === '' ? null : $descripcion);
        } catch (PDOException $e) {
            // Si dos personas guardan el mismo nombre al mismo tiempo, las dos pasan la
            // validación, pero la base rechaza la segunda por el UNIQUE (error 1062).
            if ($this->esNombreDuplicado($e)) {
                $this->volverConErrores('crear', 0, $nombre, $descripcion,
                    ['nombre' => 'Ya existe una categoría con ese nombre.']);
            }
            throw $e; // cualquier otro error: página de error
        }

        // 4. Mensaje flash (se muestra una sola vez, en la siguiente página) y redirect.
        flash('success', "Se creó la categoría \"$nombre\".");
        $this->redirect($this->urlListado());
    }

    /**
     * POST /admin/categorias/actualizar
     */
    public function actualizar(): void
    {
        $idCategoria = (int) ($_POST['idCategoria'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));

        // El id viene de un campo oculto: el usuario lo puede cambiar, así que se comprueba.
        if (Categoria::buscarPorId($idCategoria) === null) {
            flash('danger', 'La categoría que intentas editar no existe.');
            $this->redirect($this->urlListado());
        }

        $errores = $this->validar($nombre, $descripcion, $idCategoria);
        if ($errores) {
            $this->volverConErrores('editar', $idCategoria, $nombre, $descripcion, $errores);
        }

        try {
            Categoria::actualizar($idCategoria, $nombre, $descripcion === '' ? null : $descripcion);
        } catch (PDOException $e) {
            if ($this->esNombreDuplicado($e)) {
                $this->volverConErrores('editar', $idCategoria, $nombre, $descripcion,
                    ['nombre' => 'Ya existe una categoría con ese nombre.']);
            }
            throw $e;
        }

        flash('success', "Se guardaron los cambios de \"$nombre\".");
        $this->redirect($this->urlListado());
    }

    /**
     * POST /admin/categorias/cambiar-estado
     * Recibe el estado que se quiere dejar (activar = 1 o 0), no "invertir": si el
     * usuario envía el formulario dos veces, el resultado es el mismo.
     */
    public function cambiarEstado(): void
    {
        $idCategoria = (int) ($_POST['idCategoria'] ?? 0);
        $activar = ($_POST['activar'] ?? '') === '1';

        $categoria = Categoria::buscarPorId($idCategoria);
        if ($categoria === null) {
            flash('danger', 'La categoría no existe.');
            $this->redirect($this->urlListado());
        }

        // REGLA: no se desactiva una categoría que todavía tiene equipos activos.
        // El módulo de Inventario debe respetar la regla inversa: no se pueden
        // asignar equipos (al crear o editar un equipo) a una categoría inactiva.
        if (!$activar) {
            $equiposActivos = Categoria::contarEquiposActivos($idCategoria);
            if ($equiposActivos > 0) {
                $texto = $equiposActivos === 1 ? '1 equipo activo' : "$equiposActivos equipos activos";
                flash('danger', "No se puede desactivar: la categoría tiene $texto. "
                    . 'Desactívalos o cámbialos de categoría primero.');
                $this->redirect($this->urlListado());
            }
        }

        Categoria::cambiarActiva($idCategoria, $activar);

        $accion = $activar ? 'activó' : 'desactivó';
        flash('success', "Se $accion la categoría \"{$categoria['nombre']}\".");
        $this->redirect($this->urlListado());
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados del controlador
    // ---------------------------------------------------------------------

    /**
     * Devuelve los errores por campo: ['nombre' => 'mensaje']. Vacío = todo bien.
     *
     * @param int $idCategoria 0 al crear; el id de la categoría al editar
     */
    private function validar(string $nombre, string $descripcion, int $idCategoria): array
    {
        $errores = [];

        // Los navegadores envían UTF-8; si llega otra codificación, MySQL rechazaría el texto.
        if (!mb_check_encoding($nombre, 'UTF-8') || !mb_check_encoding($descripcion, 'UTF-8')) {
            return ['nombre' => 'El texto tiene caracteres no válidos. Escríbelo de nuevo.'];
        }

        if ($nombre === '') {
            $errores['nombre'] = 'Escribe el nombre de la categoría.';
        } elseif (mb_strlen($nombre) > 80) {
            // mb_strlen cuenta letras (á = 1); strlen contaría bytes (á = 2).
            $errores['nombre'] = 'El nombre no puede tener más de 80 caracteres.';
        } elseif (Categoria::existeNombre($nombre, $idCategoria)) {
            $errores['nombre'] = 'Ya existe una categoría con ese nombre.';
        }

        if (mb_strlen($descripcion) > 250) {
            $errores['descripcion'] = 'La descripción no puede tener más de 250 caracteres.';
        }

        return $errores;
    }

    /**
     * Guarda en la sesión lo necesario para volver a abrir el modal con lo que el
     * usuario escribió y los errores, y redirige al listado. La vista lo lee en
     * index() y app.js abre el modal al cargar la página.
     */
    private function volverConErrores(string $modo, int $idCategoria, string $nombre, string $descripcion, array $errores): void
    {
        $_SESSION[self::SESION_FORMULARIO] = [
            'modo'        => $modo,          // 'crear' o 'editar'
            'idCategoria' => $idCategoria,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'errores'     => $errores,
        ];
        flash('danger', 'Revisa los datos del formulario.');
        $this->redirect($this->urlListado()); // redirect() termina la ejecución (exit)
    }

    /** Error 1062 de MySQL = valor repetido en una columna UNIQUE. */
    private function esNombreDuplicado(PDOException $e): bool
    {
        return isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062;
    }

    /**
     * URL del listado conservando la búsqueda y la página en que estaba el usuario.
     * Los formularios envían esos dos valores en campos ocultos.
     */
    private function urlListado(): string
    {
        $parametros = [];
        $buscar = trim((string) ($_POST['buscar'] ?? ''));
        $pagina = (int) ($_POST['pagina'] ?? 1);
        if ($buscar !== '') {
            $parametros['buscar'] = $buscar;
        }
        if ($pagina > 1) {
            $parametros['pagina'] = $pagina;
        }

        $url = '/admin/categorias';
        if ($parametros) {
            // http_build_query codifica los caracteres especiales: ?buscar=Martillos%20Hidr%C3%A1ulicos
            $url .= '?' . http_build_query($parametros);
        }
        return $url;
    }
}
