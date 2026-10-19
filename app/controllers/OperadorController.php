<?php
/**
 * EquipRent - Panel del Operador.
 */
declare(strict_types=1);

class OperadorController extends Controller
{
    public function __construct()
    {
        $this->requireRole('Operador');
    }

    /** GET /operador */
    public function dashboard(): void
    {
        $this->view('operador/dashboard', [
            'titulo'      => 'Panel del operador',
            'indicadores' => Indicador::resumenGeneral(),
        ]);
    }
}
