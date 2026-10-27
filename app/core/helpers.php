<?php
/**
 * EquipRent - Funciones de ayuda: configuración, URLs, escape de salida,
 * sesión, mensajes flash, token CSRF y manejo de errores.
 */
declare(strict_types=1);

/**
 * Lee un valor de config/config.local.php con notación de puntos: config('bd.host').
 */
function config(string $clave, $defecto = null)
{
    static $config = null;
    if ($config === null) {
        $config = require RAIZ . '/config/config.local.php';
    }

    $valor = $config;
    foreach (explode('.', $clave) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return $defecto;
        }
        $valor = $valor[$parte];
    }
    return $valor;
}

/**
 * Escapa texto para mostrarlo en HTML.
 */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL absoluta dentro de la aplicación: url('/login').
 */
function url(string $ruta = '/'): string
{
    return rtrim(config('app.urlBase'), '/') . '/' . ltrim($ruta, '/');
}

/**
 * Los filtros de la URL son texto. Un valor manipulado como ?buscar[]=x llega como arreglo
 * y haría fallar (string), esFecha() o in_array() con un error 500: se descarta y el filtro
 * toma su valor por defecto. Se llama una vez en app/bootstrap.php, para todas las páginas.
 * Única excepción: las listas de casillas de un formulario GET, si traen solo texto.
 */
function limpiarParametrosUrl(): void
{
    $listas = ['equipos']; // paso 1 de "Nuevo alquiler" (contratos/nuevo.php)
    foreach ($_GET as $nombre => $valor) {
        $esLista = in_array($nombre, $listas, true) && is_array($valor) && array_filter($valor, 'is_string') === $valor;
        if (!is_string($valor) && !$esLista) {
            unset($_GET[$nombre]);
        }
    }
}

/**
 * URL de un archivo dentro de public/assets.
 */
function asset(string $ruta): string
{
    return url('assets/' . ltrim($ruta, '/'));
}

// ---------------------------------------------------------------------
// Presentación
// ---------------------------------------------------------------------

/**
 * Etiqueta de color para un estado guardado en la base (equipo, contrato,
 * daño, mantenimiento, activo/inactivo): estadoBadge('FueraServicio')
 * imprime una píldora roja que dice "Fuera de servicio".
 */
function estadoBadge(string $estado): string
{
    // estado en la base => [color, texto que ve el usuario]
    $estados = [
        // equipo
        'Disponible'        => ['verde', 'Disponible'],
        'Alquilado'         => ['naranja', 'Alquilado'],
        'Revision'          => ['gris', 'En revisión'],
        'Mantenimiento'     => ['azul', 'Mantenimiento'],
        'FueraServicio'     => ['rojo', 'Fuera de servicio'],
        // contrato
        'Pendiente'         => ['naranja', 'Pendiente'],
        'Aprobado'          => ['azul', 'Aprobado'],
        'Rechazado'         => ['rojo', 'Rechazado'],
        'Activo'            => ['verde', 'Activo'],
        'Finalizado'        => ['gris', 'Finalizado'],
        'Cancelado'         => ['gris', 'Cancelado'],
        // daño
        'Reportado'         => ['naranja', 'Reportado'],
        'EnEvaluacion'      => ['azul', 'En evaluación'],
        'EnReparacion'      => ['azul', 'En reparación'],
        'Resuelto'          => ['verde', 'Resuelto'],
        'Descartado'        => ['gris', 'Descartado'],
        // mantenimiento
        'Diagnostico'       => ['azul', 'Diagnóstico'],
        'EsperandoRepuesto' => ['naranja', 'Esperando repuesto'],
        // registros que se activan o desactivan (categorías, usuarios...)
        'Activa'            => ['verde', 'Activa'],
        'Inactiva'          => ['gris', 'Inactiva'],
        'Inactivo'          => ['gris', 'Inactivo'],
        // situación de una tarifa (Tarifa::listarPorEquipo)
        'Vigente'           => ['verde', 'Vigente'],
        'Programada'        => ['azul', 'Programada'],
        'Historica'         => ['gris', 'Histórica'],
    ];

    [$color, $texto] = $estados[$estado] ?? ['gris', $estado];
    return '<span class="estado estado-' . $color . '">' . e($texto) . '</span>';
}

/**
 * URL pública de una foto de equipo, o null si no hay foto o si el archivo no existe
 * (por ejemplo, las fotos de 02_seed.sql apuntan a archivos que todavía no se subieron).
 *
 * @param string|null $ruta valor de fotografiaEquipo.urlFotografia o de v_catalogoEquipo.fotoPrincipal,
 *                          relativo a public/ (por ejemplo 'uploads/equipos/abc.jpg')
 */
function urlFotoEquipo(?string $ruta): ?string
{
    if ($ruta === null || $ruta === '') {
        return null;
    }
    // Solo se aceptan rutas dentro de public/uploads/ y sin "..", para no servir otros archivos.
    if (strpos($ruta, 'uploads/') !== 0 || strpos($ruta, '..') !== false) {
        return null;
    }
    return is_file(RAIZ . '/public/' . $ruta) ? url($ruta) : null;
}

/**
 * Foto de un equipo lista para imprimir: un <img> si el archivo existe, o el
 * marcador gris (icono) si no hay foto o el archivo no existe. Úsala en todo
 * lugar que muestre fotoPrincipal o urlFotografia (inventario, catálogo...).
 * El tamaño lo da el contenedor (por ejemplo .tarjeta-equipo-imagen o .foto-galeria).
 *
 * @param string $icono icono del marcador (Bootstrap Icons), por ejemplo el de la categoría
 */
function imagenEquipo(?string $ruta, string $textoAlternativo, string $icono = 'bi-image'): string
{
    $url = urlFotoEquipo($ruta);
    if ($url !== null) {
        return '<img class="foto-equipo" src="' . e($url) . '" alt="' . e($textoAlternativo) . '" loading="lazy">';
    }
    return '<span class="foto-equipo-vacia" role="img" aria-label="' . e('Sin foto: ' . $textoAlternativo) . '">'
        . '<i class="bi ' . e($icono) . '" aria-hidden="true"></i></span>';
}

/**
 * Fecha de la base (Y-m-d o Y-m-d H:i:s) en formato de El Salvador: 15/03/2024 o 15/03/2024 14:30.
 */
function fechaCorta(?string $fecha, bool $conHora = false): string
{
    if ($fecha === null || $fecha === '') {
        return '—';
    }
    $marca = strtotime($fecha);
    return $marca === false ? $fecha : date($conHora ? 'd/m/Y H:i' : 'd/m/Y', $marca);
}

/** ¿Es una fecha real en formato AAAA-MM-DD? esFecha('2026-02-30') -> false. */
function esFecha(string $valor): bool
{
    $fecha = DateTime::createFromFormat('Y-m-d', $valor);
    return $fecha !== false && $fecha->format('Y-m-d') === $valor;
}

/**
 * Días de atraso contra la fecha de fin de un contrato, a la fecha dada (o a hoy).
 * diasAtraso('2026-10-15', '2026-10-25 09:00') -> 10; a tiempo -> 0.
 */
function diasAtraso(string $fechaFin, ?string $fecha = null): int
{
    $dias = (new DateTime($fechaFin))->diff(new DateTime(substr($fecha ?? date('Y-m-d'), 0, 10)));
    return $dias->invert ? 0 : (int) $dias->days;
}

/** Período de un alquiler: periodo('2026-11-03', '2026-11-05') -> '03/11/2026 – 05/11/2026'. */
function periodo(string $inicio, string $fin): string
{
    return fechaCorta($inicio) . ' – ' . fechaCorta($fin);
}

/**
 * Icono (Bootstrap Icons) de una categoría, para tarjetas y marcadores sin foto.
 * Las categorías que no estén aquí usan bi-tools.
 */
function iconoCategoria(?string $categoria): string
{
    $iconos = [
        'Minicargadores'        => 'bi-truck-front-fill',
        'Excavadoras'           => 'bi-cone-striped',
        'Martillos Hidráulicos' => 'bi-hammer',
        'Compactadores'         => 'bi-layers-fill',
        'Generadores Diésel'    => 'bi-lightning-charge-fill',
        'Retroexcavadoras'      => 'bi-truck-flatbed',
    ];
    return $iconos[$categoria] ?? 'bi-tools';
}

/**
 * Separa "Motor: 49 HP; Capacidad: 860 kg" en filas [['Motor', '49 HP'], ['Capacidad', '860 kg']].
 * Una parte sin ":" queda con etiqueta vacía.
 */
function especificaciones(?string $texto): array
{
    $filas = [];
    foreach (preg_split('/[;\n]+/', (string) $texto) as $parte) {
        $parte = trim($parte);
        if ($parte !== '') {
            $dosPuntos = strpos($parte, ':');
            $filas[] = $dosPuntos === false
                ? ['', $parte]
                : [trim(substr($parte, 0, $dosPuntos)), trim(substr($parte, $dosPuntos + 1))];
        }
    }
    return $filas;
}

/**
 * Datos para dibujar un mes (componentes/calendario.php), semanas de domingo a sábado.
 *
 * @param string $mes     'AAAA-MM'; si no es válido o es anterior al mes actual, se usa el mes actual
 * @param array  $rangos  [['desde' => 'AAAA-MM-DD', 'hasta' => 'AAAA-MM-DD' | null], ...] días ocupados
 *                        (hasta = null: ocupado sin fecha de fin, por ejemplo un alquiler atrasado)
 * @return array ['titulo', 'anterior' (o null), 'siguiente', 'semanas' => [[día, ...], ...]]
 *               cada día: ['numero', 'fecha', 'estado' => 'fuera' | 'pasado' | 'ocupado' | 'libre']
 */
function calendarioMes(string $mes, array $rangos): array
{
    $mesActual = date('Y-m');
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) || $mes < $mesActual) {
        $mes = $mesActual;
    }
    $nombres = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto',
                'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $primerDia = new DateTimeImmutable($mes . '-01');
    $hoy = date('Y-m-d');

    // Desde el domingo de la primera semana hasta el sábado de la última.
    $dia = $primerDia->modify('-' . $primerDia->format('w') . ' days');
    $ultimo = $primerDia->modify('last day of this month');
    $fin = $ultimo->modify('+' . (6 - (int) $ultimo->format('w')) . ' days');

    $semanas = [];
    $numeroDia = 0; // posición del día en la cuadrícula: cada 7 días empieza otra semana
    while ($dia <= $fin) {
        $fecha = $dia->format('Y-m-d');
        if ($dia->format('Y-m') !== $mes) {
            $estado = 'fuera';
        } elseif ($fecha < $hoy) {
            $estado = 'pasado';
        } else {
            $estado = 'libre';
            foreach ($rangos as $rango) {
                if ($fecha >= $rango['desde'] && ($rango['hasta'] === null || $fecha <= $rango['hasta'])) {
                    $estado = 'ocupado';
                    break;
                }
            }
        }
        $semanas[intdiv($numeroDia, 7)][] = [
            'numero' => (int) $dia->format('j'),
            'fecha'  => $fecha,
            'estado' => $estado,
        ];
        $numeroDia++;
        $dia = $dia->modify('+1 day');
    }

    return [
        'titulo'    => ucfirst($nombres[(int) $primerDia->format('n') - 1]) . ' ' . $primerDia->format('Y'),
        'anterior'  => $mes > $mesActual ? $primerDia->modify('-1 month')->format('Y-m') : null,
        'siguiente' => $primerDia->modify('+1 month')->format('Y-m'),
        'semanas'   => $semanas,
    ];
}

/**
 * Cálculo común de la paginación: ajusta la página pedida al rango válido.
 * paginacion(23, 9, 10) -> ['pagina' => 3, 'totalPaginas' => 3, 'desplazamiento' => 20]
 */
function paginacion(int $total, int $paginaPedida, int $porPagina): array
{
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min(max(1, $paginaPedida), $totalPaginas);
    return [
        'pagina'         => $pagina,
        'totalPaginas'   => $totalPaginas,
        'desplazamiento' => ($pagina - 1) * $porPagina,
    ];
}

/**
 * Equipos de un contrato en una línea, ya escapado: el primero y cuántos más.
 * resumenEquipos('Minicargador Bobcat S70', 3) -> 'Minicargador Bobcat S70 <span ...>+2 más</span>'
 */
function resumenEquipos(string $primero, int $cantidad): string
{
    return e($primero) . ($cantidad > 1 ? ' <span class="texto-suave small">+' . ($cantidad - 1) . ' más</span>' : '');
}

/**
 * Códigos que se muestran calculados a partir del id (no son columnas de la base):
 * codigo('CAT', 3) -> 'CAT-03'; codigo('REP', 7, '2026-10-26') -> 'REP-2026-007'.
 * Con fecha lleva el año, y el número va con 3 cifras; sin fecha, con 2.
 */
function codigo(string $prefijo, int $id, ?string $fecha = null): string
{
    return $fecha === null
        ? sprintf('%s-%02d', $prefijo, $id)
        : sprintf('%s-%s-%03d', $prefijo, substr($fecha, 0, 4), $id);
}

/**
 * Número que tendrá la próxima fila de una tabla (su AUTO_INCREMENT), para mostrar un
 * código antes de guardar (CAT-12, ENT-2026-021). information_schema_stats_expiry = 0 pide
 * el valor actual y no el de la caché. Solo es para mostrar: el código real sale del id.
 */
function siguienteId(string $tabla): int
{
    $permitidas = ['categoria', 'entrega']; // nombres fijos: nunca un valor del usuario
    if (!in_array($tabla, $permitidas, true)) {
        throw new InvalidArgumentException("Tabla no permitida: $tabla");
    }
    $conexion = Database::conexion();
    $conexion->exec('SET SESSION information_schema_stats_expiry = 0');
    $sentencia = $conexion->prepare('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $sentencia->execute([$tabla]);
    return (int) $sentencia->fetchColumn();
}

/**
 * Grupos de pestañas (componentes/pestanas.php): [ruta base, [clave => [texto, icono]]].
 * La pestaña se elige con ?tab= en la ruta base; la primera es la de por defecto.
 */
function pestanas(string $grupo): array
{
    $grupos = [
        'alquileres'    => ['/contratos', ['todos' => ['Todos', 'bi-list-ul'], 'solicitudes' => ['Solicitudes pendientes', 'bi-inbox']]],
        'operacion'     => ['/entregas', ['entregas' => ['Entregas pendientes', 'bi-truck'], 'devoluciones' => ['Devoluciones pendientes', 'bi-arrow-left-right']]],
        'mantenimiento' => ['/mantenimiento', ['ordenes' => ['Órdenes', 'bi-wrench-adjustable'], 'danos' => ['Daños', 'bi-exclamation-triangle']]],
        'configuracion' => ['/admin/configuracion', [
            'categorias'    => ['Categorías', 'bi-tags'],
            'desactivacion' => ['Desactivación de equipos', 'bi-slash-circle'],
            'reportes'      => ['Ver reportes', 'bi-bar-chart'],
            'usuarios'      => ['Usuarios y roles', 'bi-people'],
        ]],
    ];
    return $grupos[$grupo];
}

/** La pestaña pedida en ?tab= si existe en el grupo; si no, la primera. */
function pestanaActual(string $grupo): string
{
    $claves = array_keys(pestanas($grupo)[1]);
    return in_array($_GET['tab'] ?? '', $claves, true) ? $_GET['tab'] : $claves[0];
}

/** Monto en dólares: dinero(1500) -> '$1,500.00'. */
function dinero($monto): string
{
    return $monto === null || $monto === '' ? '—' : '$' . number_format((float) $monto, 2);
}

/**
 * Iniciales para el avatar: iniciales('Carlos Andrés', 'Rodríguez') -> 'CR'.
 */
function iniciales(string $nombres, string $apellidos): string
{
    $primera = mb_substr(trim($nombres), 0, 1);
    $segunda = mb_substr(trim($apellidos), 0, 1);
    return mb_strtoupper($primera . $segunda);
}

// ---------------------------------------------------------------------
// Sesión
// ---------------------------------------------------------------------

function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $rutaBase = parse_url(config('app.urlBase'), PHP_URL_PATH) ?: '/';
    session_name('EQUIPRENT');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => rtrim($rutaBase, '/') . '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/**
 * Cierra la sesión por completo (datos, cookie e identificador).
 */
function destruirSesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'],
        ]);
    }
    session_destroy();
}

// ---------------------------------------------------------------------
// Mensajes flash (se muestran una sola vez)
// Tipos: success, danger, warning, info (clases de alerta de Bootstrap)
// ---------------------------------------------------------------------

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/**
 * Contraseña temporal recién generada (usuario nuevo o restablecida): viaja en la sesión
 * hasta la siguiente página, que la muestra en el cuadro componentes/claveTemporal.php.
 */
function flashClaveTemporal(string $nombre, string $correo, string $clave): void
{
    $_SESSION['claveTemporal'] = ['nombre' => $nombre, 'correo' => $correo, 'clave' => $clave];
}

/** Toma la contraseña temporal y la BORRA de la sesión: al recargar ya no aparece. */
function tomarClaveTemporal(): ?array
{
    $dato = $_SESSION['claveTemporal'] ?? null;
    unset($_SESSION['claveTemporal']);
    return $dato;
}

function flashes(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

/**
 * Valor que el usuario escribió antes de una redirección (para volver a llenar el formulario).
 */
function valorAnterior(string $campo, string $defecto = ''): string
{
    $valor = $_SESSION['valoresAnteriores'][$campo] ?? $defecto;
    unset($_SESSION['valoresAnteriores'][$campo]);
    return (string) $valor;
}

function guardarValoresAnteriores(array $valores): void
{
    $_SESSION['valoresAnteriores'] = $valores;
}

// ---------------------------------------------------------------------
// CSRF: todo formulario POST imprime csrfCampo(); el Router lo verifica.
// ---------------------------------------------------------------------

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCampo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrfToken()) . '">';
}

function csrfValido(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// ---------------------------------------------------------------------
// Errores
// ---------------------------------------------------------------------

/**
 * ¿La base rechazó el INSERT/UPDATE por un valor repetido en una columna UNIQUE?
 * (error 1062 de MySQL). Con $restriccion se comprueba cuál: el mensaje de MySQL
 * incluye el nombre del UNIQUE, por ejemplo 'uq_equipo_serie'.
 */
function esValorDuplicado(PDOException $e, string $restriccion = ''): bool
{
    if (!isset($e->errorInfo[1]) || (int) $e->errorInfo[1] !== 1062) {
        return false;
    }
    return $restriccion === '' || stripos((string) ($e->errorInfo[2] ?? ''), $restriccion) !== false;
}

/**
 * Último recurso para cualquier excepción no atrapada.
 * Con debug muestra el detalle; sin debug, una página genérica.
 */
function manejarExcepcion(Throwable $e): void
{
    error_log(sprintf('[EquipRent] %s: %s en %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    try {
        (new ErrorController())->errorInterno($e);
    } catch (Throwable $otro) {
        echo 'Ocurrió un error.';
    }
}
