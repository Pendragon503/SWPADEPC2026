<?php
/**
 * EquipRent - Configuración del sistema (solo Administrador): junta en pestañas (?tab=)
 * páginas que ya existen. Cada pestaña la muestra el controlador de siempre, así que sus
 * formularios, reglas y URL anteriores no cambian.
 *
 * Rutas (app/rutas.php):
 *   GET /admin/configuracion?tab=categorias|desactivacion|reportes|usuarios  -> index
 */
declare(strict_types=1);

class ConfiguracionController extends Controller
{
    public function __construct()
    {
        $this->requireRole('Administrador');
    }

    /** GET /admin/configuracion */
    public function index(): void
    {
        match (pestanaActual('configuracion')) {
            'reportes' => (new ReporteController())->index(),
            'usuarios' => (new UsuarioController())->index(),
            default    => (new CategoriaController())->index(),
        };
    }
}
