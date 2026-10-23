<?php
/**
 * EquipRent - Consultas del catálogo que ve el cliente (vista v_catalogoEquipo).
 * Por ahora solo lo que usa la página de inicio del cliente; el resto llega
 * con el módulo de Catálogo.
 */
declare(strict_types=1);

class Catalogo
{
    /**
     * Un equipo se puede ALQUILAR (aparece en el catálogo del cliente) si está activo,
     * tiene tarifa diaria vigente, está Disponible o Alquilado (puede estar libre en
     * otras fechas) y no tiene un mantenimiento abierto. Es el mismo criterio de
     * sp_equiposDisponibles, sin las fechas. Se usa con el alias "v" de v_catalogoEquipo.
     */
    public const CONDICION_ALQUILABLE = "v.activo = TRUE
                AND v.estado IN ('Disponible', 'Alquilado')
                AND v.precioDiario IS NOT NULL
                AND NOT EXISTS (SELECT 1 FROM mantenimiento m
                                 WHERE m.idEquipo = v.idEquipo AND m.estado NOT IN ('Finalizado', 'Cancelado'))";

    /** Columnas que necesita una tarjeta del catálogo (componentes/tarjetaEquipo.php). */
    private const COLUMNAS_TARJETA = 'v.idEquipo, v.nombre, v.marca, v.modelo, v.idCategoria, v.categoria, v.precioDiario, v.fotoPrincipal';

    /**
     * WHERE del catálogo: siempre los alquilables, más el texto y la categoría.
     * Los valores del usuario van en $parametros, nunca dentro del SQL.
     *
     * @param array $filtros ['buscar' => string, 'idCategoria' => int]
     */
    private static function condicionesCatalogo(array $filtros): array
    {
        $condiciones = [self::CONDICION_ALQUILABLE];
        $parametros = [];
        if ($filtros['buscar'] !== '') {
            $condiciones[] = '(v.nombre LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ? OR v.categoria LIKE ?)';
            $patron = '%' . $filtros['buscar'] . '%';
            array_push($parametros, $patron, $patron, $patron, $patron);
        }
        if ($filtros['idCategoria'] > 0) {
            $condiciones[] = 'v.idCategoria = ?';
            $parametros[] = $filtros['idCategoria'];
        }
        return ['WHERE ' . implode(' AND ', $condiciones), $parametros];
    }

    public static function contar(array $filtros): int
    {
        [$where, $parametros] = self::condicionesCatalogo($filtros);
        $sentencia = Database::conexion()->prepare("SELECT COUNT(*) FROM v_catalogoEquipo v $where");
        $sentencia->execute($parametros);
        return (int) $sentencia->fetchColumn();
    }

    /** Una página del catálogo (solo las columnas de la tarjeta). */
    public static function listar(array $filtros, int $limite, int $desplazamiento): array
    {
        [$where, $parametros] = self::condicionesCatalogo($filtros);
        $sentencia = Database::conexion()->prepare(
            'SELECT ' . self::COLUMNAS_TARJETA . " FROM v_catalogoEquipo v $where
              ORDER BY v.categoria, v.nombre, v.codigoInventario
              LIMIT ? OFFSET ?"
        );
        $posicion = 1;
        foreach ($parametros as $valor) {
            $sentencia->bindValue($posicion++, $valor);
        }
        $sentencia->bindValue($posicion++, $limite, PDO::PARAM_INT);
        $sentencia->bindValue($posicion, $desplazamiento, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /**
     * Un equipo alquilable con todo lo que muestra su detalle, o null si no existe o
     * no se puede alquilar (por ejemplo, dado de baja o en mantenimiento).
     */
    public static function equipo(int $idEquipo): ?array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT ' . self::COLUMNAS_TARJETA . ', v.codigoInventario, v.descripcion, v.caracteristicasTecnicas,
                    v.condicionesUso, v.depositoSugerido
               FROM v_catalogoEquipo v
              WHERE v.idEquipo = ? AND ' . self::CONDICION_ALQUILABLE
        );
        $sentencia->execute([$idEquipo]);
        $fila = $sentencia->fetch();
        return $fila === false ? null : $fila;
    }

    /**
     * Rangos ocupados del equipo desde hoy, con el mismo criterio de fn_conflictosEquipo:
     * contratos Pendiente, Aprobado o Activo cuya línea no se ha devuelto. Un Activo
     * vencido sin devolución bloquea sin fecha de fin (hasta = NULL).
     * Solo fechas: el cliente no ve contratos ni datos de otros clientes.
     */
    public static function fechasOcupadas(int $idEquipo): array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT c.fechaInicio AS desde,
                    CASE WHEN c.estado = 'Activo' AND c.fechaFin < CURDATE() THEN NULL ELSE c.fechaFin END AS hasta
               FROM detalleContrato dc
               JOIN contrato c ON c.idContrato = dc.idContrato
              WHERE dc.idEquipo = ?
                AND c.estado IN ('Pendiente', 'Aprobado', 'Activo')
                AND (c.fechaFin >= CURDATE() OR c.estado = 'Activo')
                AND NOT EXISTS (SELECT 1 FROM detalleDevolucion dd WHERE dd.idDetalleContrato = dc.idDetalleContrato)
              ORDER BY c.fechaInicio"
        );
        $sentencia->execute([$idEquipo]);
        return $sentencia->fetchAll();
    }

    /**
     * Equipos libres en un rango (RF-05), con el procedimiento de la base
     * sp_equiposDisponibles, que ya usa fn_conflictosEquipo y calcula días y subtotal.
     * Las fechas deben venir validadas (Solicitud::validarFechas).
     */
    public static function disponibles(string $inicio, string $fin, int $idCategoria): array
    {
        $sentencia = Database::conexion()->prepare('CALL sp_equiposDisponibles(?, ?, ?)');
        $sentencia->bindValue(1, $inicio);
        $sentencia->bindValue(2, $fin);
        $sentencia->bindValue(3, $idCategoria > 0 ? $idCategoria : null, $idCategoria > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $sentencia->execute();
        $filas = $sentencia->fetchAll();
        $sentencia->closeCursor(); // un CALL deja resultados pendientes: se liberan antes de la siguiente consulta
        return $filas;
    }

    /** Categorías con cuántos equipos alquilables tienen, en una sola consulta (panel de filtros). */
    public static function categoriasConTotal(): array
    {
        return Database::conexion()->query(
            'SELECT v.idCategoria, v.categoria, COUNT(*) AS total
               FROM v_catalogoEquipo v
              WHERE ' . self::CONDICION_ALQUILABLE . '
              GROUP BY v.idCategoria, v.categoria
              ORDER BY v.categoria'
        )->fetchAll();
    }

    /**
     * Las $limite categorías con más equipos alquilables, a partir del resultado de
     * categoriasConTotal() (así el Inicio no hace otra consulta para lo mismo).
     */
    public static function populares(array $categorias, int $limite): array
    {
        usort($categorias, function (array $a, array $b): int {
            return [$b['total'], $a['categoria']] <=> [$a['total'], $b['categoria']]; // más equipos primero; empate: por nombre
        });
        return array_slice($categorias, 0, $limite);
    }

    /**
     * Un equipo alquilable por categoría (el primero registrado), para "Equipos destacados".
     * ROW_NUMBER() numera los equipos de cada categoría en una sola pasada; se queda el 1.
     */
    public static function equiposDestacados(int $limite): array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT idEquipo, nombre, marca, modelo, idCategoria, categoria, precioDiario, fotoPrincipal
               FROM (SELECT " . self::COLUMNAS_TARJETA . ",
                            ROW_NUMBER() OVER (PARTITION BY v.idCategoria ORDER BY v.idEquipo) AS numero
                       FROM v_catalogoEquipo v
                      WHERE " . self::CONDICION_ALQUILABLE . ") primeros
              WHERE numero = 1
              ORDER BY categoria
              LIMIT ?"
        );
        $sentencia->bindValue(1, $limite, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /**
     * Inventario para el panel del administrador (los primeros $limite equipos por código).
     */
    public static function inventarioResumido(int $limite): array
    {
        $sentencia = Database::conexion()->prepare(
            "SELECT codigoInventario, nombre, categoria, precioDiario, estado
               FROM v_catalogoEquipo
              WHERE activo = TRUE
              ORDER BY codigoInventario
              LIMIT ?"
        );
        $sentencia->bindValue(1, $limite, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }
}
