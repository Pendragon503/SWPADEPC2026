<?php
/**
 * EquipRent - Autenticación (RF-01) y datos del usuario en sesión.
 */
declare(strict_types=1);

final class Auth
{
    /** Panel de inicio de cada rol. */
    private const INICIO_POR_ROL = [
        'Administrador' => '/admin',
        'Operador'      => '/operador',
        'Cliente'       => '/cliente',
    ];

    /**
     * Valida correo y contraseña e inicia la sesión.
     * Devuelve null si todo salió bien, o el mensaje de error para el usuario.
     */
    public static function intentar(string $correo, string $clave): ?string
    {
        $usuario = Usuario::buscarPorCorreo($correo);

        if ($usuario === null) {
            // Se verifica igual contra un hash cualquiera para que la respuesta
            // tarde lo mismo exista o no el correo.
            password_verify($clave, '$2y$10$E/4uXsn2DzR7wWxp.p3gy.almIRJeV1ZXknvdU/p7LM7gw8TlHfSm');
            return 'Correo o contraseña incorrectos.';
        }
        if (!password_verify($clave, $usuario['claveHash'])) {
            return 'Correo o contraseña incorrectos.';
        }
        if (!$usuario['activo'] || !$usuario['rolActivo']) {
            return 'Tu cuenta está desactivada. Contacta al administrador.';
        }
        if (!isset(self::INICIO_POR_ROL[$usuario['rol']])) {
            return 'Tu cuenta no tiene un rol válido. Contacta al administrador.';
        }
        if ($usuario['rol'] === 'Cliente' && $usuario['idCliente'] === null) {
            return 'Tu usuario no tiene un registro de cliente asociado. Contacta al administrador.';
        }

        if (password_needs_rehash($usuario['claveHash'], PASSWORD_DEFAULT)) {
            Usuario::actualizarClaveHash((int) $usuario['idUsuario'], password_hash($clave, PASSWORD_DEFAULT));
        }
        Usuario::actualizarUltimoAcceso((int) $usuario['idUsuario']);

        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'idUsuario' => (int) $usuario['idUsuario'],
            'nombres'   => $usuario['nombres'],
            'apellidos' => $usuario['apellidos'],
            'correo'    => $usuario['correo'],
            'rol'       => $usuario['rol'],
            'idCliente' => $usuario['idCliente'] === null ? null : (int) $usuario['idCliente'],
        ];
        unset($_SESSION['csrf']);
        Database::sincronizarUsuario();

        return null;
    }

    /**
     * Se llama en CADA petición (app/bootstrap.php). Antes, activo solo se revisaba al
     * iniciar sesión: un usuario desactivado seguía trabajando hasta salir. Ahora, si hay
     * sesión, UNA consulta corta por clave primaria revisa que el usuario y su rol sigan
     * activos y que el rol no haya cambiado; si no, cierra la sesión y manda al login.
     */
    public static function verificarVigencia(): void
    {
        if (!self::estaAutenticado()) {
            return;
        }
        $sentencia = Database::conexion()->prepare(
            'SELECT u.activo AND r.activo AS vigente, r.nombre AS rol
               FROM usuario u JOIN rol r ON r.idRol = u.idRol
              WHERE u.idUsuario = ?'
        );
        $sentencia->execute([self::id()]);
        $fila = $sentencia->fetch();
        if ($fila !== false && $fila['vigente'] && $fila['rol'] === self::rol()) {
            return;
        }

        self::cerrarSesion();
        flash('warning', $fila !== false && $fila['vigente']
            ? 'Tu rol cambió. Inicia sesión de nuevo.'
            : 'Tu cuenta fue desactivada. Contacta al administrador.');
        header('Location: ' . url('/login'));
        exit;
    }

    public static function cerrarSesion(): void
    {
        destruirSesion();
        iniciarSesion();
        session_regenerate_id(true);
        Database::sincronizarUsuario();
    }

    public static function estaAutenticado(): bool
    {
        return isset($_SESSION['usuario']);
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['usuario']['idUsuario'] ?? null;
    }

    public static function rol(): ?string
    {
        return $_SESSION['usuario']['rol'] ?? null;
    }

    /** idCliente del usuario en sesión (solo para el rol Cliente). */
    public static function idCliente(): ?int
    {
        return $_SESSION['usuario']['idCliente'] ?? null;
    }

    public static function nombreCompleto(): string
    {
        $u = self::usuario();
        return $u === null ? '' : trim($u['nombres'] . ' ' . $u['apellidos']);
    }

    /** Ruta del panel según el rol en sesión, o /login si no hay sesión. */
    public static function rutaInicio(): string
    {
        return self::INICIO_POR_ROL[self::rol() ?? ''] ?? '/login';
    }
}
