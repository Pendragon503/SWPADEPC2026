<?php
/**
 * EquipRent - "Mi perfil" de los tres roles: ver y editar los propios datos (sin el rol
 * ni el código) y cambiar la contraseña pidiendo la actual. Siempre sobre el usuario
 * de la sesión: ningún id llega por el formulario.
 *
 * Rutas (app/rutas.php):
 *   GET  /perfil         -> index
 *   POST /perfil/datos   -> datos
 *   POST /perfil/clave   -> clave
 */
declare(strict_types=1);

class PerfilController extends Controller
{
    private const SESION_FORMULARIO = 'formularioPerfil';

    /** Campos que cada persona edita de sí misma; el cliente agrega sus datos comerciales. */
    private const CAMPOS = ['nombres', 'apellidos', 'documentoIdentidad', 'correo', 'telefono', 'direccion'];
    private const CAMPOS_CLIENTE = ['razonSocial', 'documentoTributario', 'contactoAlterno', 'telefonoAlterno'];

    public function __construct()
    {
        $this->requireRole('Administrador', 'Operador', 'Cliente');
    }

    /** GET /perfil */
    public function index(): void
    {
        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);
        $esCliente = Auth::rol() === 'Cliente';
        $actual = $this->actual();

        $this->view('perfil/index', [
            'titulo'     => 'Mi perfil',
            'menuActivo' => $esCliente ? 'perfil' : 'Mi perfil',
            'esCliente'  => $esCliente,
            'perfil'     => $actual,
            'valores'    => $formulario['valores'] ?? $actual,
            'errores'    => $formulario['errores'] ?? [],
        ], $esCliente ? 'cliente' : 'principal');
    }

    /** POST /perfil/datos */
    public function datos(): void
    {
        $actual = $this->actual();
        $esCliente = Auth::rol() === 'Cliente';
        $datos = $actual; // el tipo de persona, las observaciones y el rol no se cambian aquí
        foreach (array_merge(self::CAMPOS, $esCliente ? self::CAMPOS_CLIENTE : []) as $campo) {
            $datos[$campo] = trim((string) ($_POST[$campo] ?? ''));
        }
        $datos['correo'] = mb_strtolower($datos['correo']);
        $datos['documentoIdentidad'] = mb_strtoupper($datos['documentoIdentidad']);
        $datos = array_map(function ($valor) {
            return $valor ?? ''; // NULL de la base -> texto vacío, como espera la validación
        }, $datos);

        $errores = $esCliente ? Cliente::validar($datos, $actual) : Usuario::validarPersona($datos, (int) Auth::id());
        if (!$errores) {
            try {
                $esCliente ? Cliente::actualizar($actual, $datos) : Usuario::actualizar((int) Auth::id(), $datos);
                Auth::refrescarDatos($datos);
                flash('success', 'Se guardaron tus datos.');
                $this->redirect('/perfil');
            } catch (PDOException $e) {
                foreach (Cliente::DUPLICADOS as $restriccion => [$campo, $mensaje]) {
                    if (esValorDuplicado($e, $restriccion)) {
                        $errores[$campo] = $mensaje;
                    }
                }
                if (!$errores) {
                    throw $e;
                }
            }
        }
        $_SESSION[self::SESION_FORMULARIO] = ['valores' => $datos, 'errores' => $errores];
        flash('danger', 'Revisa tus datos.');
        $this->redirect('/perfil');
    }

    /** POST /perfil/clave */
    public function clave(): void
    {
        $nueva = (string) ($_POST['clave'] ?? '');
        $errores = Usuario::validarCambioClave((int) Auth::id(), (string) ($_POST['claveActual'] ?? ''), $nueva, (string) ($_POST['confirmacion'] ?? ''));
        if ($errores) {
            $_SESSION[self::SESION_FORMULARIO] = ['errores' => $errores]; // las contraseñas nunca se guardan
            flash('danger', 'No se cambió la contraseña.');
        } else {
            Usuario::actualizarClaveHash((int) Auth::id(), password_hash($nueva, PASSWORD_DEFAULT));
            session_regenerate_id(true); // sesión nueva tras cambiar la contraseña
            flash('success', 'Se cambió tu contraseña.');
        }
        $this->redirect('/perfil');
    }

    /** Datos actuales del usuario en sesión (el cliente, con su fila de cliente). */
    private function actual(): array
    {
        return Auth::rol() === 'Cliente' ? Cliente::buscarPorId((int) Auth::idCliente()) : Usuario::buscarPorId((int) Auth::id());
    }
}
