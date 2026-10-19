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

// Administrador: categorías (módulo de ejemplo).
// El Router no admite parámetros en la URL: los POST llevan el id en un campo
// oculto y las páginas GET de detalle lo reciben como ?id=5.
$router->get('/admin/categorias', 'CategoriaController@index');
$router->post('/admin/categorias/guardar', 'CategoriaController@guardar');
$router->post('/admin/categorias/actualizar', 'CategoriaController@actualizar');
$router->post('/admin/categorias/cambiar-estado', 'CategoriaController@cambiarEstado');
