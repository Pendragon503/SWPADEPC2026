<?php
/**
 * EquipRent - Fotografías de un equipo (se manejan desde su detalle).
 *
 * Rutas (todas POST, con el id en un campo oculto, y todas vuelven al detalle):
 *   /admin/equipos/fotos/subir      -> subir           varias a la vez (input name="fotos[]" multiple)
 *   /admin/equipos/fotos/principal  -> marcarPrincipal la que se muestra en el catálogo
 *   /admin/equipos/fotos/ordenar    -> ordenar         subir o bajar un lugar
 *   /admin/equipos/fotos/eliminar   -> eliminar        borra el registro y el archivo
 *
 * El formulario de subida necesita enctype="multipart/form-data" para que PHP reciba los archivos.
 */
declare(strict_types=1);

class FotografiaEquipoController extends Controller
{
    public function __construct()
    {
        $this->requireRole('Administrador');
    }

    /** POST /admin/equipos/fotos/subir */
    public function subir(): void
    {
        $idEquipo = (int) ($_POST['idEquipo'] ?? 0);
        $equipo = Equipo::buscarPorId($idEquipo);
        if ($equipo === null) {
            flash('danger', 'El equipo no existe.');
            $this->redirect('/admin/equipos');
        }

        $archivos = $this->leerArchivos('fotos');
        $errores = FotografiaEquipo::validarArchivos($archivos);
        if ($errores) {
            // Varios mensajes: uno por archivo con problema.
            foreach ($errores as $error) {
                flash('danger', $error);
            }
            flash('warning', 'No se guardó ninguna foto. Corrige los archivos y vuelve a subirlos.');
            $this->redirectAlDetalle($idEquipo);
        }

        $cantidad = FotografiaEquipo::guardar($idEquipo, $archivos, $equipo['nombre']);
        flash('success', $cantidad === 1 ? 'Se subió 1 foto.' : "Se subieron $cantidad fotos.");
        $this->redirectAlDetalle($idEquipo);
    }

    /** POST /admin/equipos/fotos/principal */
    public function marcarPrincipal(): void
    {
        $foto = $this->fotoOListado();
        FotografiaEquipo::marcarPrincipal($foto);
        flash('success', 'Se cambió la foto principal. Es la que se muestra en el catálogo.');
        $this->redirectAlDetalle((int) $foto['idEquipo']);
    }

    /** POST /admin/equipos/fotos/ordenar  (direccion = subir | bajar) */
    public function ordenar(): void
    {
        $foto = $this->fotoOListado();
        $direccion = ($_POST['direccion'] ?? '') === 'subir' ? 'subir' : 'bajar';
        FotografiaEquipo::mover($foto, $direccion);
        $this->redirectAlDetalle((int) $foto['idEquipo']);
    }

    /** POST /admin/equipos/fotos/eliminar */
    public function eliminar(): void
    {
        $foto = $this->fotoOListado();
        FotografiaEquipo::eliminar($foto);
        flash('success', $foto['esPrincipal']
            ? 'Se eliminó la foto. La siguiente pasó a ser la principal.'
            : 'Se eliminó la foto.');
        $this->redirectAlDetalle((int) $foto['idEquipo']);
    }

    // ---------------------------------------------------------------------
    // Ayudantes privados
    // ---------------------------------------------------------------------

    /**
     * $_FILES con un input múltiple trae un arreglo por propiedad
     * (name => [a, b], tmp_name => [a, b]...). Aquí se convierte en una lista
     * de archivos, uno por foto: [['name' => a, 'tmp_name' => a, ...], ...].
     * Los campos vacíos (el usuario no eligió nada) se descartan.
     */
    private function leerArchivos(string $campo): array
    {
        $subidos = $_FILES[$campo] ?? null;
        if (!is_array($subidos) || !is_array($subidos['name'] ?? null)) {
            return [];
        }

        $archivos = [];
        foreach ($subidos['name'] as $i => $nombre) {
            if ($subidos['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $archivos[] = [
                'name'     => (string) $nombre,
                'tmp_name' => (string) $subidos['tmp_name'][$i],
                'size'     => (int) $subidos['size'][$i],
                'error'    => (int) $subidos['error'][$i],
            ];
        }
        return $archivos;
    }

    /** La foto del campo oculto idFotografia o, si no existe, vuelve al listado. */
    private function fotoOListado(): array
    {
        $foto = FotografiaEquipo::buscarPorId((int) ($_POST['idFotografia'] ?? 0));
        if ($foto === null) {
            flash('danger', 'La foto no existe.');
            $this->redirect('/admin/equipos');
        }
        return $foto;
    }

    private function redirectAlDetalle(int $idEquipo): void
    {
        $this->redirect('/admin/equipos/detalle?id=' . $idEquipo . '#fotos');
    }
}
