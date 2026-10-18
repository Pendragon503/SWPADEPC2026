<?php
/**
 * EquipRent - Tabla de rutas. $router lo crea public/index.php.
 *
 * @var Router $router
 */
declare(strict_types=1);

$router->get('/', 'AuthController@inicio');

// Autenticación
$router->get('/login', 'AuthController@mostrarLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');

// Paneles por rol
$router->get('/admin', 'AdminController@dashboard');
$router->get('/operador', 'OperadorController@dashboard');
$router->get('/cliente', 'ClienteController@dashboard');
