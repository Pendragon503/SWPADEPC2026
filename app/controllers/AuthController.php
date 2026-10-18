<?php
/**
 * EquipRent - Inicio y cierre de sesión (RF-01).
 */
declare(strict_types=1);

class AuthController extends Controller
{
    /** GET / : al panel del rol o al login. */
    public function inicio(): void
    {
        $this->redirect(Auth::rutaInicio());
    }

    /** GET /login */
    public function mostrarLogin(): void
    {
        if (Auth::estaAutenticado()) {
            $this->redirect(Auth::rutaInicio());
        }
        $this->view('auth/login', ['titulo' => 'Iniciar sesión'], 'simple');
    }

    /** POST /login */
    public function login(): void
    {
        $correo = trim((string) ($_POST['correo'] ?? ''));
        $clave = (string) ($_POST['clave'] ?? '');

        if ($correo === '' || $clave === '') {
            $error = 'Escribe tu correo y tu contraseña.';
        } else {
            $error = Auth::intentar($correo, $clave);
        }

        if ($error !== null) {
            flash('danger', $error);
            guardarValoresAnteriores(['correo' => $correo]);
            $this->redirect('/login');
        }

        $this->redirect(Auth::rutaInicio());
    }

    /** POST /logout */
    public function logout(): void
    {
        Auth::cerrarSesion();
        flash('success', 'Cerraste sesión correctamente.');
        $this->redirect('/login');
    }
}
