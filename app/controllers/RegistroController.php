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

    /**
     * POST /registro: al terminar, al login con un mensaje (no inicia la sesión solo).
     * Desde aquí siempre se crea una persona Natural; las empresas (razón social y NIT)
     * las registra el personal en Clientes.
     */
    public function guardar(): void
    {
        $nombreCompleto = trim((string) ($_POST['nombreCompleto'] ?? ''));
        $partes = Usuario::separarNombre($nombreCompleto);
        $datos = $partes + [
            'documentoIdentidad' => mb_strtoupper(trim((string) ($_POST['documentoIdentidad'] ?? ''))),
            'correo'             => mb_strtolower(trim((string) ($_POST['correo'] ?? ''))),
            'tipoPersona'        => 'Natural',
        ] + array_fill_keys(['telefono', 'direccion', 'razonSocial', 'documentoTributario', 'contactoAlterno', 'telefonoAlterno', 'observaciones'], '');
        $clave = (string) ($_POST['clave'] ?? '');

        $errores = Usuario::validarNombreCompleto($partes) + Cliente::validar($datos, null)
            + Usuario::validarClave($clave, (string) ($_POST['confirmacion'] ?? ''));
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

        // Se guarda lo escrito (nunca la contraseña) para volver a llenar el formulario.
        $_SESSION[self::SESION_FORMULARIO] = ['valores' => $datos + ['nombreCompleto' => $nombreCompleto], 'errores' => $errores];
        flash('danger', 'Revisa los datos del formulario.');
        $this->redirect('/registro');
    }
}
