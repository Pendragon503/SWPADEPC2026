<?php
/**
 * EquipRent - Usuarios internos (RF-02): administradores y operadores. Solo Administrador.
 * Sigue el patrón de Clientes: el modelo (Usuario) valida y aplica las reglas.
 *
 * Rutas (app/rutas.php):
 *   GET  /admin/usuarios?buscar=&rol=&activo=1&pagina=   -> index
 *   POST /admin/usuarios/guardar                          -> guardar        crear (contraseña temporal)
 *   POST /admin/usuarios/actualizar                       -> actualizar     editar datos y rol
 *   POST /admin/usuarios/cambiar-estado                   -> cambiarEstado  activar / desactivar
 *   POST /admin/usuarios/restablecer-clave                -> restablecerClave  contraseña temporal nueva
 */
declare(strict_types=1);

class UsuarioController extends Controller
{
    private const POR_PAGINA = 10;
    private const SESION_FORMULARIO = 'formularioUsuario';

    public function __construct()
    {
        $this->requireRole('Administrador');
    }

    /** GET /admin/usuarios */
    public function index(): void
    {
        $filtros = $this->leerFiltros($_GET);
        $total = Usuario::contar($filtros);
        $paginas = paginacion($total, (int) ($_GET['pagina'] ?? 1), self::POR_PAGINA);
        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);

        $this->view('usuarios/index', $paginas + [
            'titulo'     => 'Configuración del sistema',
            'menuActivo' => 'Configuración',
            'filtros'    => $filtros,
            'total'      => $total,
            'usuarios'   => Usuario::listar($filtros, self::POR_PAGINA, $paginas['desplazamiento']),
            'formulario' => $formulario,
        ]);
    }

    /** POST /admin/usuarios/guardar: la contraseña temporal se muestra UNA sola vez. */
    public function guardar(): void
    {
        $datos = $this->leerFormulario();
        $this->siHayErroresVolver($datos, Usuario::validar($datos, null));

        $clave = Usuario::generarClaveTemporal();
        $this->siEsDuplicadoVolver(function () use ($datos, $clave): void {
            Usuario::crear($datos, $clave);
        }, $datos);
        flash('success', "Se creó el usuario {$datos['correo']} ({$datos['rol']}). Podrá cambiar su contraseña en \"Mi perfil\".");
        flashClaveTemporal("{$datos['nombres']} {$datos['apellidos']}", $datos['correo'], $clave);
        $this->redirect($this->urlVolver());
    }

    /** POST /admin/usuarios/actualizar */
    public function actualizar(): void
    {
        $actual = $this->usuario((int) ($_POST['idUsuario'] ?? 0));
        $datos = $this->leerFormulario();
        $this->siHayErroresVolver($datos, Usuario::validar($datos, $actual));

        $this->siEsDuplicadoVolver(function () use ($actual, $datos): void {
            Usuario::actualizar((int) $actual['idUsuario'], $datos);
        }, $datos);
        flash('success', "Se guardaron los cambios de {$datos['correo']}.");
        $this->redirect($this->urlVolver());
    }

    /** POST /admin/usuarios/cambiar-estado (activar = 1 o 0): no se borra nada. */
    public function cambiarEstado(): void
    {
        $usuario = $this->usuario((int) ($_POST['idUsuario'] ?? 0));
        $activar = ($_POST['activar'] ?? '') === '1';
        $error = Usuario::validarCambioActivo($usuario, $activar);
        if ($error !== null) {
            flash('danger', $error);
        } else {
            Usuario::cambiarActivo((int) $usuario['idUsuario'], $activar);
            flash('success', $activar ? "Se activó {$usuario['correo']}." : "Se desactivó {$usuario['correo']}: su sesión se cerrará en su siguiente clic.");
        }
        $this->redirect($this->urlVolver());
    }

    /** POST /admin/usuarios/restablecer-clave: contraseña temporal nueva, mostrada una sola vez. */
    public function restablecerClave(): void
    {
        $usuario = $this->usuario((int) ($_POST['idUsuario'] ?? 0));
        $error = Usuario::validarRestablecer($usuario);
        if ($error !== null) {
            flash('danger', $error);
        } else {
            $clave = Usuario::generarClaveTemporal();
            Usuario::actualizarClaveHash((int) $usuario['idUsuario'], password_hash($clave, PASSWORD_DEFAULT));
            flash('success', "Se restableció la contraseña de {$usuario['correo']}: la anterior ya no funciona.");
            flashClaveTemporal("{$usuario['nombres']} {$usuario['apellidos']}", $usuario['correo'], $clave);
        }
        $this->redirect($this->urlVolver());
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    /** Usuario interno (Administrador u Operador), o 404. */
    private function usuario(int $idUsuario): array
    {
        $usuario = Usuario::buscarPorId($idUsuario);
        if ($usuario === null || !in_array($usuario['rol'], Usuario::ROLES_INTERNOS, true)) {
            throw new ErrorHttp('Usuario no encontrado', 404);
        }
        return $usuario;
    }

    private function leerFormulario(): array
    {
        $datos = ['idUsuario' => (int) ($_POST['idUsuario'] ?? 0)];
        foreach (array_slice(Usuario::CAMPOS_FORMULARIO, 1) as $campo) {
            $datos[$campo] = trim((string) ($_POST[$campo] ?? ''));
        }
        $datos['correo'] = mb_strtolower($datos['correo']);
        $datos['documentoIdentidad'] = mb_strtoupper($datos['documentoIdentidad']);
        return $datos;
    }

    /** Ejecuta el guardado; si la base rechaza un correo o documento repetido (1062), vuelve al modal. */
    private function siEsDuplicadoVolver(callable $guardar, array $datos): void
    {
        try {
            $guardar();
        } catch (PDOException $e) {
            foreach (Usuario::DUPLICADOS as $restriccion => [$campo, $mensaje]) {
                if (esValorDuplicado($e, $restriccion)) {
                    $this->siHayErroresVolver($datos, [$campo => $mensaje]);
                }
            }
            throw $e;
        }
    }

    /** Guarda lo escrito y los errores y vuelve al listado; el modal se abre solo. */
    private function siHayErroresVolver(array $datos, array $errores): void
    {
        if ($errores) {
            $_SESSION[self::SESION_FORMULARIO] = ['valores' => $datos, 'errores' => $errores];
            flash('danger', 'Revisa los datos del formulario.');
            $this->redirect($this->urlVolver());
        }
    }

    /** Listado con los filtros del campo oculto "volver". */
    private function urlVolver(): string
    {
        parse_str((string) ($_POST['volver'] ?? ''), $parametros);
        $consulta = array_filter($this->leerFiltros($parametros) + ['pagina' => $parametros['pagina'] ?? ''], function ($valor) {
            return $valor !== '' && $valor !== '1';
        });
        return '/admin/usuarios' . ($consulta ? '?' . http_build_query($consulta) : '');
    }

    private function leerFiltros(array $origen): array
    {
        $activo = (string) ($origen['activo'] ?? '1');
        return [
            'buscar' => trim((string) ($origen['buscar'] ?? '')),
            'rol'    => in_array($origen['rol'] ?? '', Usuario::ROLES_INTERNOS, true) ? $origen['rol'] : '',
            'activo' => in_array($activo, ['1', '0', 'todos'], true) ? $activo : '1',
        ];
    }
}
