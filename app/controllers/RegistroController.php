<?php
/**
 * EquipRent - Registro de clientes desde el login ("Crear una cuenta"). Público.
 * Reutiliza Cliente: las mismas validaciones y Cliente::crear(), que en UNA
 * transacción crea el usuario con rol Cliente, su fila en cliente y su código.
 *
 * Rutas (app/rutas.php):
 *   GET  /registro  -> mostrar
 *   POST /registro  -> guardar
 */
declare(strict_types=1);

class RegistroController extends Controller
{
    private const SESION_FORMULARIO = 'formularioRegistro';

    /** Con sesión iniciada no tiene sentido registrarse: va a su panel. */
    public function __construct()
    {
        if (Auth::estaAutenticado()) {
            $this->redirect(Auth::rutaInicio());
        }
    }

    /** GET /registro */
    public function mostrar(): void
    {
        $formulario = $_SESSION[self::SESION_FORMULARIO] ?? null;
        unset($_SESSION[self::SESION_FORMULARIO]);
        $this->view('auth/registro', [
            'titulo'  => 'Crear una cuenta',
            'valores' => $formulario['valores'] ?? [],
            'errores' => $formulario['errores'] ?? [],
        ], 'simple');
    }

    /** POST /registro: al terminar, al login con un mensaje (no inicia la sesión solo). */
    public function guardar(): void
    {
        $datos = ['observaciones' => '', 'contactoAlterno' => '', 'telefonoAlterno' => '']; // solo los llena el personal
        foreach (['nombres', 'apellidos', 'documentoIdentidad', 'correo', 'telefono', 'direccion', 'tipoPersona', 'razonSocial', 'documentoTributario'] as $campo) {
            $datos[$campo] = trim((string) ($_POST[$campo] ?? ''));
        }
        $datos['correo'] = mb_strtolower($datos['correo']);
        $datos['documentoIdentidad'] = mb_strtoupper($datos['documentoIdentidad']);
        $datos['documentoTributario'] = mb_strtoupper($datos['documentoTributario']);
        $clave = (string) ($_POST['clave'] ?? '');

        $errores = Cliente::validar($datos, null) + Usuario::validarClave($clave, (string) ($_POST['confirmacion'] ?? ''));
        if (!$errores) {
            try {
                $nuevo = Cliente::crear($datos, $clave);
                flash('success', "¡Listo! Tu cuenta {$nuevo['codigoCliente']} quedó creada. Inicia sesión con tu correo y tu contraseña.");
                $this->redirect('/login');
            } catch (PDOException $e) {
                // Alguien registró el mismo correo o documento al mismo tiempo (error 1062).
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

        $_SESSION[self::SESION_FORMULARIO] = ['valores' => $datos, 'errores' => $errores]; // nunca se guarda la contraseña
        flash('danger', 'Revisa los datos del formulario.');
        $this->redirect('/registro');
    }
}
