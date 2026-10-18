<?php
/**
 * EquipRent - Conexión PDO única (singleton) y perezosa: solo se conecta
 * la primera vez que se pide, así las páginas sin datos no dependen de MySQL.
 *
 * Al conectar ejecuta SET @app_idUsuario con el usuario en sesión, que
 * necesitan los triggers de bitácora de la tabla equipo.
 */
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function conexion(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::conectar();
            self::sincronizarUsuario();
        }
        return self::$pdo;
    }

    /**
     * Copia el usuario en sesión a @app_idUsuario. Se llama al conectar,
     * después del login y después del logout. Si aún no hay conexión no hace nada.
     */
    public static function sincronizarUsuario(): void
    {
        if (self::$pdo === null) {
            return;
        }
        $sentencia = self::$pdo->prepare('SET @app_idUsuario = ?');
        $sentencia->execute([Auth::id()]);
    }

    /**
     * Lee la configuración aquí dentro (sin recibirla como parámetro) para que
     * el usuario y la contraseña nunca aparezcan en una traza de error.
     */
    private static function conectar(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            config('bd.host'),
            (int) config('bd.puerto'),
            config('bd.nombre'),
            config('bd.charset', 'utf8mb4')
        );

        try {
            return new PDO($dsn, config('bd.usuario'), config('bd.clave'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            // El mensaje original de PDO incluye el usuario; no se reenvía.
            throw new ErrorConexion(self::explicarFallo((int) $e->getCode()));
        }
    }

    private static function explicarFallo(int $codigo): string
    {
        switch ($codigo) {
            case 1045:
                $causa = 'acceso denegado; revisa el usuario y la contraseña en config/config.local.php';
                break;
            case 1049:
                $causa = 'la base de datos no existe; ejecuta database/01_schema.sql';
                break;
            case 2002:
                $causa = 'el servidor no responde; revisa que el servicio MySQL esté encendido';
                break;
            default:
                $causa = 'error del servidor';
        }
        return sprintf(
            'No se pudo conectar a MySQL en %s:%d, base "%s" (código %d: %s).',
            config('bd.host'),
            (int) config('bd.puerto'),
            config('bd.nombre'),
            $codigo,
            $causa
        );
    }
}
