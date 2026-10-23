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
        // Una sola consulta de categorías sirve para el buscador y para "Categorías populares".
        $categorias = Catalogo::categoriasConTotal();

        $this->view('cliente/dashboard', [
            'titulo'      => 'Inicio',
            'menuActivo'  => 'inicio',
            'categorias'  => $categorias,
            'populares'   => Catalogo::populares($categorias, 4),
            'destacados'  => Catalogo::equiposDestacados(4),
            'alquileres'  => Contrato::buscar(['idCliente' => (int) Auth::idCliente()], 'recientes', 3, 0),
        ], 'cliente');
    }
}
