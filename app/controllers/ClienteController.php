<?php
/**
 * EquipRent - Panel del Cliente. Usa su propio layout (menú superior).
 */
declare(strict_types=1);

class ClienteController extends Controller
{
    public function __construct()
    {
        $this->requireRole('Cliente');
    }

    /** GET /cliente */
    public function dashboard(): void
    {
        $this->view('cliente/dashboard', [
            'titulo'            => 'Inicio',
            'menuActivo'        => 'inicio',
            'categorias'        => Catalogo::categoriasPopulares(4),
            'destacados'        => Catalogo::equiposDestacados(4),
            'opcionesCategoria' => Catalogo::categoriasActivas(),
        ], 'cliente');
    }
}
