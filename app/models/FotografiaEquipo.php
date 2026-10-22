<?php
/**
 * EquipRent - Modelo de la tabla fotografiaEquipo.
 *
 * Los archivos se guardan en public/uploads/equipos/ con un nombre generado al
 * azar (nunca el nombre que trae el archivo), y en la base se guarda la ruta
 * relativa a public/: 'uploads/equipos/3f9a...c1.jpg'.
 *
 * Excepción a la regla "no se borran registros": una foto sí se elimina (registro
 * y archivo), porque no tiene historial ni otros datos que dependan de ella.
 */
declare(strict_types=1);

class FotografiaEquipo
{
    /** Tipos permitidos: tipo real del archivo (MIME) => extensión con que se guarda. */
    public const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public const TAMANO_MAXIMO = 2 * 1024 * 1024; // 2 MB por foto
    public const MAXIMO_POR_ENVIO = 10;

    /** Carpeta relativa a public/. */
    private const CARPETA = 'uploads/equipos';

    // ---------------------------------------------------------------------
    // Validaciones
    // ---------------------------------------------------------------------

    /**
     * Valida los archivos subidos. Devuelve una lista de mensajes; vacía = todos bien.
     * Si un archivo falla, no se guarda ninguno (para que el usuario corrija y vuelva a subir).
     *
     * El tipo se comprueba por el CONTENIDO del archivo (finfo y getimagesize), no por
     * la extensión ni por el tipo que dice el navegador: un "foto.jpg" que en realidad
     * es un script se rechaza.
     *
     * @param array $archivos lista de ['name', 'tmp_name', 'size', 'error'] (ver el controlador)
     */
    public static function validarArchivos(array $archivos): array
    {
        if (!$archivos) {
            return ['Elige al menos una foto.'];
        }
        if (count($archivos) > self::MAXIMO_POR_ENVIO) {
            return ['Puedes subir hasta ' . self::MAXIMO_POR_ENVIO . ' fotos a la vez.'];
        }

        $errores = [];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        foreach ($archivos as $archivo) {
            $nombre = '"' . $archivo['name'] . '"';

            if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE) {
                $errores[] = "$nombre pesa más de 2 MB.";
                continue;
            }
            if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
                $errores[] = "$nombre no se pudo subir. Inténtalo de nuevo.";
                continue;
            }
            if ($archivo['size'] > self::TAMANO_MAXIMO) {
                $errores[] = "$nombre pesa más de 2 MB.";
                continue;
            }

            $tipoReal = $finfo->file($archivo['tmp_name']);
            $imagen = @getimagesize($archivo['tmp_name']); // false si no es una imagen que PHP pueda leer
            if (!isset(self::TIPOS[$tipoReal]) || $imagen === false || $imagen['mime'] !== $tipoReal) {
                $errores[] = "$nombre no es una imagen JPG, PNG o WebP válida.";
            }
        }
        return $errores;
    }

    // ---------------------------------------------------------------------
    // Consultas y cambios
    // ---------------------------------------------------------------------

    /** Fotos del equipo en el orden en que se muestran (la principal primero si empatan). */
    public static function listarPorEquipo(int $idEquipo): array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT idFotografia, urlFotografia, textoAlternativo, ordenVisualizacion, esPrincipal, idEquipo
               FROM fotografiaEquipo
              WHERE idEquipo = ?
              ORDER BY ordenVisualizacion, esPrincipal DESC, idFotografia'
        );
        $sentencia->execute([$idEquipo]);
        return $sentencia->fetchAll();
    }

    public static function buscarPorId(int $idFotografia): ?array
    {
        $sentencia = Database::conexion()->prepare(
            'SELECT idFotografia, urlFotografia, textoAlternativo, ordenVisualizacion, esPrincipal, idEquipo
               FROM fotografiaEquipo WHERE idFotografia = ?'
        );
        $sentencia->execute([$idFotografia]);
        $fila = $sentencia->fetch();
        return $fila === false ? null : $fila;
    }

    /**
     * Mueve los archivos (ya validados) a public/uploads/equipos/ y los registra.
     * Si el equipo no tenía foto principal, la primera que se sube pasa a serlo.
     * Devuelve cuántas fotos se guardaron.
     */
    public static function guardar(int $idEquipo, array $archivos, string $textoAlternativo): int
    {
        $carpeta = RAIZ . '/public/' . self::CARPETA;
        if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true)) {
            throw new RuntimeException('No se pudo crear la carpeta de fotos de equipos.');
        }

        $conexion = Database::conexion();
        $movidos = []; // para borrar los archivos si la base falla
        $conexion->beginTransaction();
        try {
            $orden = self::ultimoOrden($idEquipo);
            $hayPrincipal = self::tienePrincipal($idEquipo);
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $insertar = $conexion->prepare(
                'INSERT INTO fotografiaEquipo (urlFotografia, textoAlternativo, ordenVisualizacion, esPrincipal, idEquipo)
                 VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($archivos as $archivo) {
                // Nombre al azar + la extensión del tipo REAL: nunca se usa el nombre original.
                $extension = self::TIPOS[$finfo->file($archivo['tmp_name'])];
                $ruta = self::CARPETA . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
                if (!move_uploaded_file($archivo['tmp_name'], RAIZ . '/public/' . $ruta)) {
                    throw new RuntimeException('No se pudo guardar una foto en el servidor.');
                }
                $movidos[] = $ruta;

                $esPrincipal = !$hayPrincipal;
                $hayPrincipal = true;
                $insertar->execute([$ruta, mb_substr($textoAlternativo, 0, 150), ++$orden, $esPrincipal ? 1 : 0, $idEquipo]);
            }
            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollBack();
            foreach ($movidos as $ruta) {
                self::borrarArchivo($ruta);
            }
            throw $e;
        }
        return count($movidos);
    }

    /**
     * Marca una foto como principal. En UNA transacción: primero se quita la marca a
     * la anterior y luego se pone a la nueva (el UNIQUE de principalDe no deja tener dos).
     */
    public static function marcarPrincipal(array $foto): void
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $conexion->prepare('UPDATE fotografiaEquipo SET esPrincipal = FALSE WHERE idEquipo = ? AND esPrincipal = TRUE')
                ->execute([$foto['idEquipo']]);
            $conexion->prepare('UPDATE fotografiaEquipo SET esPrincipal = TRUE WHERE idFotografia = ?')
                ->execute([$foto['idFotografia']]);
            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollBack();
            throw $e;
        }
    }

    /**
     * Sube o baja una foto un lugar. Primero numera las fotos 1, 2, 3... (por si hay
     * números repetidos) y luego intercambia la foto con su vecina.
     *
     * @param string $direccion 'subir' o 'bajar'
     */
    public static function mover(array $foto, string $direccion): void
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $ids = array_column(self::listarPorEquipo((int) $foto['idEquipo']), 'idFotografia');
            $posicion = array_search($foto['idFotografia'], $ids);
            $vecina = $direccion === 'subir' ? $posicion - 1 : $posicion + 1;

            if ($posicion !== false && isset($ids[$vecina])) {
                [$ids[$posicion], $ids[$vecina]] = [$ids[$vecina], $ids[$posicion]];
            }
            self::renumerar($ids);
            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollBack();
            throw $e;
        }
    }

    /**
     * Elimina la foto (registro y archivo). Si era la principal, la siguiente en orden
     * pasa a ser la principal.
     */
    public static function eliminar(array $foto): void
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $conexion->prepare('DELETE FROM fotografiaEquipo WHERE idFotografia = ?')->execute([$foto['idFotografia']]);

            $restantes = self::listarPorEquipo((int) $foto['idEquipo']);
            if ($foto['esPrincipal'] && $restantes) {
                $conexion->prepare('UPDATE fotografiaEquipo SET esPrincipal = TRUE WHERE idFotografia = ?')
                    ->execute([$restantes[0]['idFotografia']]);
            }
            self::renumerar(array_column($restantes, 'idFotografia'));
            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollBack();
            throw $e;
        }
        // El archivo se borra después del commit: si la base fallara, la foto seguiría completa.
        self::borrarArchivo($foto['urlFotografia']);
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    private static function ultimoOrden(int $idEquipo): int
    {
        $sentencia = Database::conexion()->prepare('SELECT COALESCE(MAX(ordenVisualizacion), 0) FROM fotografiaEquipo WHERE idEquipo = ?');
        $sentencia->execute([$idEquipo]);
        return (int) $sentencia->fetchColumn();
    }

    private static function tienePrincipal(int $idEquipo): bool
    {
        $sentencia = Database::conexion()->prepare('SELECT COUNT(*) FROM fotografiaEquipo WHERE idEquipo = ? AND esPrincipal = TRUE');
        $sentencia->execute([$idEquipo]);
        return (int) $sentencia->fetchColumn() > 0;
    }

    /** Deja ordenVisualizacion = 1, 2, 3... en el orden de la lista de ids. */
    private static function renumerar(array $ids): void
    {
        $sentencia = Database::conexion()->prepare('UPDATE fotografiaEquipo SET ordenVisualizacion = ? WHERE idFotografia = ?');
        foreach (array_values($ids) as $indice => $idFotografia) {
            $sentencia->execute([$indice + 1, $idFotografia]);
        }
    }

    /** Borra el archivo solo si está dentro de uploads/equipos (nunca otra ruta). */
    private static function borrarArchivo(string $ruta): void
    {
        if (strpos($ruta, self::CARPETA . '/') !== 0 || strpos($ruta, '..') !== false) {
            return;
        }
        $archivo = RAIZ . '/public/' . $ruta;
        if (is_file($archivo)) {
            unlink($archivo);
        }
    }
}
