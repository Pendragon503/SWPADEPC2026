<?php
/**
 * EquipRent - Gestión de clientes (RF-03), para Administrador y Operador.
 *
 * Se llama GestionClienteController porque ClienteController es el panel del
 * rol Cliente. Sigue el patrón de Categorías e Inventario: el controlador lee los
 * datos, llama al modelo (Cliente), que valida y aplica las reglas, y decide qué mostrar.
 *
 * Rutas (app/rutas.php):
 *   GET  /clientes                 -> index          listado con búsqueda, filtros y paginación
 *   POST /clientes/guardar         -> guardar        crear usuario + cliente (modal)
 *   POST /clientes/actualizar      -> actualizar     editar (mismo modal)
 *   POST /clientes/cambiar-estado  -> cambiarEstado  activar / desactivar el usuario
 */
declare(strict_types=1);

class GestionClienteController extends Controller
{
    private const POR_PAGINA = 10;
    private const SESION_FORMULARIO = 'formularioCliente';

    public function __construct()
    {
        // RF-03: el administrador y el operador gestionan clientes.
        $this->requireRole('Administrador', 'Operador');
    }

    /**
     * GET /clientes?buscar=&tipoPersona=&activo=1&pagina=1
     */
    public function index(): void
    {
        $filtros = $this->leerFiltros($_GET);
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));

        $total = Cliente::contar($filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }
        $desplazamiento = ($pagina - 1) * self::POR_PAGINA;

        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);

        $this->view('clientes/index', [
            'titulo'         => 'Clientes',
            'menuActivo'     => 'Clientes',
            'clientes'       => Cliente::listar($filtros, self::POR_PAGINA, $desplazamiento),
            'filtros'        => $filtros,
            'pagina'         => $pagina,
            'totalPaginas'   => $totalPaginas,
            'total'          => $total,
            'desplazamiento' => $desplazamiento,
            'formulario'     => $formulario,
        ]);
    }

    /**
     * POST /clientes/guardar
     * Crea el usuario (rol Cliente, contraseña temporal) y el cliente en una transacción.
     * La contraseña temporal se muestra UNA sola vez, en el mensaje de éxito.
     */
    public function guardar(): void
    {
        $datos = $this->leerFormulario();

        $errores = Cliente::validar($datos, null);
        if ($errores) {
            $this->volverConErrores('crear', 0, $datos, $errores);
        }

        $claveTemporal = Usuario::generarClaveTemporal();
        try {
            $nuevo = Cliente::crear($datos, $claveTemporal);
        } catch (PDOException $e) {
            $this->siEsDuplicadoVolver($e, 'crear', 0, $datos);
            throw $e;
        }

        // Solo aquí se conoce la contraseña sin cifrar (en la base queda su hash): va al cuadro de la contraseña temporal.
        flash('success', "Se registró el cliente {$nuevo['codigoCliente']}. Su usuario es {$datos['correo']}.");
        flashClaveTemporal("{$datos['nombres']} {$datos['apellidos']} ({$nuevo['codigoCliente']})", $datos['correo'], $claveTemporal);
        $this->redirect($this->urlVolver());
    }

    /**
     * POST /clientes/actualizar
     * No cambia la contraseña ni el código del cliente.
     */
    public function actualizar(): void
    {
        $idCliente = (int) ($_POST['idCliente'] ?? 0);
        $datos = $this->leerFormulario();

        $actual = Cliente::buscarPorId($idCliente);
        if ($actual === null) {
            flash('danger', 'El cliente que intentas editar no existe.');
            $this->redirect($this->urlVolver());
        }

        $errores = Cliente::validar($datos, $actual);
        if ($errores) {
            $this->volverConErrores('editar', $idCliente, $datos, $errores);
        }

        try {
            Cliente::actualizar($actual, $datos);
        } catch (PDOException $e) {
            $this->siEsDuplicadoVolver($e, 'editar', $idCliente, $datos);
            throw $e;
        }

        flash('success', "Se guardaron los cambios de {$actual['codigoCliente']}.");
        $this->redirect($this->urlVolver());
    }

    /**
     * POST /clientes/cambiar-estado  (activar = 1 o 0)
     * Activa o desactiva el usuario del cliente; no se borra nada. Un cliente
     * desactivado no puede iniciar sesión (Auth lo rechaza).
     */
    public function cambiarEstado(): void
    {
        $idCliente = (int) ($_POST['idCliente'] ?? 0);
        $activar = ($_POST['activar'] ?? '') === '1';

        $cliente = Cliente::buscarPorId($idCliente);
        if ($cliente === null) {
            flash('danger', 'El cliente no existe.');
            $this->redirect($this->urlVolver());
        }

        $error = Cliente::validarCambioActivo($cliente, $activar);
        if ($error !== null) {
            flash('danger', $error);
            $this->redirect($this->urlVolver());
        }

        Cliente::cambiarActivo($cliente, $activar);
        flash('success', $activar
            ? "Se activó {$cliente['codigoCliente']}: ya puede iniciar sesión."
            : "Se desactivó {$cliente['codigoCliente']}: ya no puede iniciar sesión ni solicitar alquileres.");
        $this->redirect($this->urlVolver());
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    /** Lee y limpia los campos del modal. */
    private function leerFormulario(): array
    {
        $texto = function (string $campo): string {
            return trim((string) ($_POST[$campo] ?? ''));
        };
        return [
            'nombres'             => $texto('nombres'),
            'apellidos'           => $texto('apellidos'),
            'correo'              => mb_strtolower($texto('correo')), // el correo se guarda en minúsculas
            'telefono'            => $texto('telefono'),
            'documentoIdentidad'  => mb_strtoupper($texto('documentoIdentidad')),
            'direccion'           => $texto('direccion'),
            'tipoPersona'         => $texto('tipoPersona'),
            'razonSocial'         => $texto('razonSocial'),
            'documentoTributario' => mb_strtoupper($texto('documentoTributario')),
            'contactoAlterno'     => $texto('contactoAlterno'),
            'telefonoAlterno'     => $texto('telefonoAlterno'),
            'observaciones'       => $texto('observaciones'),
        ];
    }

    /**
     * Si la base rechazó un valor repetido (error 1062, por ejemplo dos personas
     * guardando el mismo correo a la vez), vuelve al modal marcando el campo repetido.
     */
    private function siEsDuplicadoVolver(PDOException $e, string $modo, int $idCliente, array $datos): void
    {
        foreach (Cliente::DUPLICADOS as $restriccion => [$campo, $mensaje]) {
            if (esValorDuplicado($e, $restriccion)) {
                $this->volverConErrores($modo, $idCliente, $datos, [$campo => $mensaje]);
            }
        }
    }

    /** Guarda lo escrito y los errores en la sesión y vuelve al listado; el modal se abre solo. */
    private function volverConErrores(string $modo, int $idCliente, array $datos, array $errores): void
    {
        $_SESSION[self::SESION_FORMULARIO] = [
            'modo'      => $modo,
            'idCliente' => $idCliente,
            'valores'   => $datos,
            'errores'   => $errores,
        ];
        flash('danger', 'Revisa los datos del formulario.');
        $this->redirect($this->urlVolver());
    }

    /** Listado con los filtros del campo oculto "volver" (solo se aceptan filtros válidos). */
    private function urlVolver(): string
    {
        parse_str((string) ($_POST['volver'] ?? ''), $parametros);
        $filtros = $this->leerFiltros($parametros);
        $pagina = max(1, (int) ($parametros['pagina'] ?? 1));

        $consulta = array_filter($filtros + ['pagina' => $pagina > 1 ? $pagina : ''], function ($valor) {
            return $valor !== '';
        });
        return '/clientes' . ($consulta ? '?' . http_build_query($consulta) : '');
    }

    /** Filtros del listado con valores por defecto; los valores desconocidos se ignoran. */
    private function leerFiltros(array $origen): array
    {
        $tipo = (string) ($origen['tipoPersona'] ?? '');
        $activo = (string) ($origen['activo'] ?? '1');
        return [
            'buscar'      => trim((string) ($origen['buscar'] ?? '')),
            'tipoPersona' => isset(Cliente::TIPOS[$tipo]) ? $tipo : '',
            'activo'      => in_array($activo, ['1', '0', 'todos'], true) ? $activo : '1',
        ];
    }
}
