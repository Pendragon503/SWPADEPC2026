<?php
/**
 * EquipRent - Panel del Administrador.
 */
declare(strict_types=1);

class AdminController extends Controller
{
    public function __construct()
    {
        $this->requireRole('Administrador');
    }

    /** GET /admin */
    public function dashboard(): void
    {
        $this->view('admin/dashboard', [
            'titulo'      => 'Panel del administrador',
            'indicadores' => Indicador::resumenGeneral(),
            'inventario'  => Catalogo::inventarioResumido(5),
            // Atrasados y próximos a finalizar: la lista de devoluciones pendientes, del más atrasado.
            'proximos'    => Contrato::buscar(['pendiente' => 'devolucion'], 'fin', 5, 0),
        ]);
    }
}
