<?php
/**
 * EquipRent - Inventario de equipos (RF-04, Administrador).
 *
 * Sigue el patrón del módulo de Categorías: el controlador lee los datos, llama
 * al modelo (Equipo), que valida y aplica las reglas, y decide qué mostrar.
 *
 * Rutas (app/rutas.php):
 *   GET  /admin/equipos                  -> index          listado con búsqueda, filtros y paginación
 *   POST /admin/equipos/guardar          -> guardar        crear (modal)
 *   POST /admin/equipos/actualizar       -> actualizar     editar (mismo modal)
 *   GET  /admin/equipos/detalle?id=5     -> detalle        ficha, fotos, tarifas e historial
 *   POST /admin/equipos/cambiar-estado   -> cambiarEstado  modal con motivo (bitácora)
 *   POST /admin/equipos/cambiar-activo   -> cambiarActivo  baja (modal con motivo) o reactivación
 *
 * Las fotos (FotografiaEquipoController) y las tarifas (TarifaController) tienen su
 * propio controlador, y vuelven siempre al detalle del equipo.
 */
declare(strict_types=1);

class EquipoController extends Controller
{
    private const POR_PAGINA = 10;

    /** Claves de sesión de los formularios con errores (se leen una vez y se borran). */
    private const SESION_FORMULARIO = 'formularioEquipo';
    private const SESION_ESTADO = 'formularioEstadoEquipo';
    private const SESION_BAJA = 'formularioBajaEquipo';

    public function __construct()
    {
        $this->requireRole('Administrador');
    }

    /**
     * GET /admin/equipos?buscar=&idCategoria=&estado=&activo=1&pagina=1
     */
    public function index(): void
    {
        $filtros = $this->leerFiltros($_GET);
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));

        $total = Equipo::contar($filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }
        $desplazamiento = ($pagina - 1) * self::POR_PAGINA;

        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);

        $this->view('equipos/index', [
            'titulo'         => 'Inventario de equipos',
            'menuActivo'     => 'Inventario',
            'equipos'        => Equipo::listar($filtros, self::POR_PAGINA, $desplazamiento),
            'indicadores'    => Indicador::resumenGeneral(),
            'categorias'     => Categoria::listar('', 1000, 0),
            'filtros'        => $filtros,
            'pagina'         => $pagina,
            'totalPaginas'   => $totalPaginas,
            'total'          => $total,
            'desplazamiento' => $desplazamiento,
            'formulario'     => $formulario,
        ]);
    }

    /**
     * POST /admin/equipos/guardar
     * Si todo sale bien, va al detalle del equipo nuevo para agregarle fotos y tarifa.
     */
    public function guardar(): void
    {
        $datos = $this->leerFormulario();

        $errores = Equipo::validar($datos, null);
        if ($errores) {
            $this->volverConErrores('crear', 0, $datos, $errores);
        }

        try {
            $idEquipo = Equipo::crear($datos);
        } catch (PDOException $e) {
            // Dos personas guardando el mismo código o serie a la vez: lo detiene el UNIQUE (1062).
            if (esValorDuplicado($e, 'uq_equipo_codigo')) {
                $this->volverConErrores('crear', 0, $datos, ['codigoInventario' => Equipo::ERROR_CODIGO_REPETIDO]);
            }
            if (esValorDuplicado($e, 'uq_equipo_serie')) {
                $this->volverConErrores('crear', 0, $datos, ['numeroSerie' => Equipo::ERROR_SERIE_REPETIDA]);
            }
            throw $e;
        }

        flash('success', "Se registró el equipo {$datos['codigoInventario']}. Agrégale sus fotos y su tarifa.");
        $this->redirect('/admin/equipos/detalle?id=' . $idEquipo);
    }

    /**
     * POST /admin/equipos/actualizar
     */
    public function actualizar(): void
    {
        $idEquipo = (int) ($_POST['idEquipo'] ?? 0);
        $datos = $this->leerFormulario();

        $actual = Equipo::buscarPorId($idEquipo);
        if ($actual === null) {
            flash('danger', 'El equipo que intentas editar no existe.');
            $this->redirect('/admin/equipos');
        }
        $datos['codigoInventario'] = $actual['codigoInventario']; // el código no se cambia al editar

        $errores = Equipo::validar($datos, $actual);
        if ($errores) {
            $this->volverConErrores('editar', $idEquipo, $datos, $errores);
        }

        try {
            Equipo::actualizar($idEquipo, $datos);
        } catch (PDOException $e) {
            if (esValorDuplicado($e, 'uq_equipo_serie')) {
                $this->volverConErrores('editar', $idEquipo, $datos, ['numeroSerie' => Equipo::ERROR_SERIE_REPETIDA]);
            }
            throw $e;
        }

        flash('success', "Se guardaron los cambios de {$actual['codigoInventario']}.");
        $this->redirect($this->urlVolver($idEquipo));
    }

    /**
     * GET /admin/equipos/detalle?id=5
     * El Router no tiene parámetros en la URL: el id llega en la query string.
     */
    public function detalle(): void
    {
        $idEquipo = (int) ($_GET['id'] ?? 0);
        $equipo = Equipo::buscarPorId($idEquipo);
        if ($equipo === null) {
            throw new ErrorHttp('Equipo no encontrado', 404);
        }

        // Formularios de este equipo que volvieron con errores (se leen una vez y se borran).
        $formularios = [];
        foreach ([self::SESION_FORMULARIO, self::SESION_ESTADO, self::SESION_BAJA] as $clave) {
            $guardado = $_SESSION[$clave] ?? null;
            $formularios[$clave] = ($guardado !== null && (int) $guardado['idEquipo'] === $idEquipo) ? $guardado : null;
            unset($_SESSION[$clave]);
        }

        $this->view('equipos/detalle', [
            'titulo'            => 'Equipo ' . $equipo['codigoInventario'],
            'menuActivo'        => 'Inventario',
            'equipo'            => $equipo,
            'historial'         => Equipo::historialEstados($idEquipo),
            'fotos'             => FotografiaEquipo::listarPorEquipo($idEquipo),
            'contratoAbierto'   => Equipo::contratoAbierto($idEquipo),
            'categorias'        => Categoria::listar('', 1000, 0),
            'formulario'        => $formularios[self::SESION_FORMULARIO],
            'formularioEstado'  => $formularios[self::SESION_ESTADO],
            'formularioBaja'    => $formularios[self::SESION_BAJA],
        ]);
    }

    /**
     * POST /admin/equipos/cambiar-estado
     * El motivo queda en la bitácora (trigger de la tabla equipo).
     */
    public function cambiarEstado(): void
    {
        $idEquipo = (int) ($_POST['idEquipo'] ?? 0);
        $estado = (string) ($_POST['estado'] ?? '');
        $motivo = trim((string) ($_POST['motivo'] ?? ''));

        $equipo = $this->equipoOListado($idEquipo);

        $errores = Equipo::validarCambioEstado($equipo, $estado, $motivo);
        if ($errores) {
            $_SESSION[self::SESION_ESTADO] = [
                'idEquipo' => $idEquipo,
                'valores'  => ['estado' => $estado, 'motivo' => $motivo],
                'errores'  => $errores,
            ];
            flash('danger', 'Revisa los datos del cambio de estado.');
            $this->redirect('/admin/equipos/detalle?id=' . $idEquipo);
        }

        Equipo::cambiarEstado($idEquipo, $estado, $motivo);
        flash('success', "{$equipo['codigoInventario']} pasó a \"" . Equipo::ESTADOS[$estado] . '".');
        $this->redirect('/admin/equipos/detalle?id=' . $idEquipo);
    }

    /**
     * POST /admin/equipos/cambiar-activo
     *   activar = 0 -> baja: pide motivo (lista) y, si es "Otro", el detalle
     *   activar = 1 -> reactivación
     */
    public function cambiarActivo(): void
    {
        $idEquipo = (int) ($_POST['idEquipo'] ?? 0);
        $activar = ($_POST['activar'] ?? '') === '1';
        $equipo = $this->equipoOListado($idEquipo);
        $urlDetalle = '/admin/equipos/detalle?id=' . $idEquipo;

        if ($activar) {
            $error = Equipo::validarReactivacion($equipo);
            if ($error !== null) {
                flash('danger', $error);
                $this->redirect($urlDetalle);
            }
            Equipo::reactivar($equipo);
            flash('success', "Se reactivó {$equipo['codigoInventario']}. Quedó Disponible.");
            $this->redirect($urlDetalle);
        }

        $motivo = (string) ($_POST['motivo'] ?? '');
        $detalle = trim((string) ($_POST['detalle'] ?? ''));

        $errores = Equipo::validarDesactivacion($equipo, $motivo, $detalle);
        if (isset($errores['general'])) {
            // Bloqueo que no depende de lo escrito (por ejemplo, un contrato abierto).
            flash('danger', $errores['general']);
            $this->redirect($urlDetalle);
        }
        if ($errores) {
            $_SESSION[self::SESION_BAJA] = [
                'idEquipo' => $idEquipo,
                'valores'  => ['motivo' => $motivo, 'detalle' => $detalle],
                'errores'  => $errores,
            ];
            flash('danger', 'Revisa el motivo de la baja.');
            $this->redirect($urlDetalle);
        }

        Equipo::desactivar($equipo, $motivo, $detalle);
        flash('success', "Se dio de baja {$equipo['codigoInventario']}: quedó Fuera de servicio e inactivo.");
        $this->redirect($urlDetalle);
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    /** El equipo del id (campo oculto que el usuario podría cambiar) o, si no existe, vuelve al listado. */
    private function equipoOListado(int $idEquipo): array
    {
        $equipo = Equipo::buscarPorId($idEquipo);
        if ($equipo === null) {
            flash('danger', 'El equipo no existe.');
            $this->redirect('/admin/equipos');
        }
        return $equipo;
    }

    /** Lee y limpia los campos del modal de crear/editar. */
    private function leerFormulario(): array
    {
        $texto = function (string $campo): string {
            return trim((string) ($_POST[$campo] ?? ''));
        };
        return [
            // El código se guarda en mayúsculas: "eq-1005" y "EQ-1005" son el mismo equipo.
            'codigoInventario'        => mb_strtoupper($texto('codigoInventario')),
            'numeroSerie'             => $texto('numeroSerie'),
            'nombre'                  => $texto('nombre'),
            'marca'                   => $texto('marca'),
            'modelo'                  => $texto('modelo'),
            'idCategoria'             => (int) ($_POST['idCategoria'] ?? 0),
            'fechaAdquisicion'        => $texto('fechaAdquisicion'),
            'valorReferencia'         => $texto('valorReferencia'),
            'descripcion'             => $texto('descripcion'),
            'caracteristicasTecnicas' => $texto('caracteristicasTecnicas'),
            'condicionesUso'          => $texto('condicionesUso'),
            // Solo informativo (campo de solo lectura del modal): no se guarda.
            'estadoActual'            => $texto('estadoActual'),
        ];
    }

    /**
     * Guarda lo escrito y los errores en la sesión y vuelve a la página de origen
     * (listado o detalle), donde el modal se abre solo (data-abrir-al-cargar).
     */
    private function volverConErrores(string $modo, int $idEquipo, array $datos, array $errores): void
    {
        $_SESSION[self::SESION_FORMULARIO] = [
            'modo'     => $modo,
            'idEquipo' => $idEquipo,
            'valores'  => $datos,
            'errores'  => $errores,
        ];
        flash('danger', 'Revisa los datos del formulario.');
        $this->redirect($this->urlVolver($idEquipo));
    }

    /**
     * A dónde volver después de un POST. Los formularios envían:
     *   origen = 'detalle' -> al detalle del equipo
     *   origen = 'listado' -> al listado, con los filtros del campo oculto "volver"
     *                         (por ejemplo "buscar=bobcat&estado=Disponible&pagina=2")
     */
    private function urlVolver(int $idEquipo): string
    {
        if (($_POST['origen'] ?? '') === 'detalle' && $idEquipo > 0) {
            return '/admin/equipos/detalle?id=' . $idEquipo;
        }

        // parse_str convierte el texto en arreglo; leerFiltros() descarta lo que no sea un filtro válido.
        parse_str((string) ($_POST['volver'] ?? ''), $parametros);
        $filtros = $this->leerFiltros($parametros);
        $pagina = max(1, (int) ($parametros['pagina'] ?? 1));

        $consulta = array_filter($filtros + ['pagina' => $pagina > 1 ? $pagina : ''], function ($valor) {
            return $valor !== '' && $valor !== 0;
        });
        return '/admin/equipos' . ($consulta ? '?' . http_build_query($consulta) : '');
    }

    /**
     * Filtros del listado, con valores por defecto. Se usa con $_GET (al listar) y con
     * los parámetros del campo oculto "volver" (al regresar después de un POST).
     * Solo se aceptan valores conocidos: un estado inventado se ignora.
     */
    private function leerFiltros(array $origen): array
    {
        $estado = (string) ($origen['estado'] ?? '');
        $activo = (string) ($origen['activo'] ?? '1'); // por defecto, solo los activos
        return [
            'buscar'      => trim((string) ($origen['buscar'] ?? '')),
            'idCategoria' => max(0, (int) ($origen['idCategoria'] ?? 0)),
            'estado'      => isset(Equipo::ESTADOS[$estado]) ? $estado : '',
            'activo'      => in_array($activo, ['1', '0', 'todos'], true) ? $activo : '1',
        ];
    }
}
