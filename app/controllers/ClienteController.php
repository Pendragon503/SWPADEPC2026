<?php
/**
 * EquipRent - Panel del Cliente.
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
        $this->view('cliente/dashboard', ['titulo' => 'Mi panel']);
    }
}
