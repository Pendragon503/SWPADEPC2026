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

// Administrador: inventario de equipos (RF-04)
$router->get('/admin/equipos', 'EquipoController@index');
$router->post('/admin/equipos/guardar', 'EquipoController@guardar');
$router->post('/admin/equipos/actualizar', 'EquipoController@actualizar');
$router->get('/admin/equipos/detalle', 'EquipoController@detalle');   // ?id=5
$router->post('/admin/equipos/cambiar-estado', 'EquipoController@cambiarEstado');
$router->post('/admin/equipos/cambiar-activo', 'EquipoController@cambiarActivo');
$router->post('/admin/equipos/fotos/subir', 'FotografiaEquipoController@subir');
$router->post('/admin/equipos/fotos/principal', 'FotografiaEquipoController@marcarPrincipal');
$router->post('/admin/equipos/fotos/ordenar', 'FotografiaEquipoController@ordenar');
$router->post('/admin/equipos/fotos/eliminar', 'FotografiaEquipoController@eliminar');
$router->post('/admin/equipos/tarifas/guardar', 'TarifaController@guardar');

// Cliente: catálogo, disponibilidad, solicitud y mis alquileres (RF-04 a RF-08)
$router->get('/cliente/catalogo', 'CatalogoController@index');
$router->get('/cliente/equipo', 'CatalogoController@equipo');   // ?id=5&mes=2026-11
$router->get('/cliente/disponibilidad', 'CatalogoController@disponibilidad');
$router->get('/cliente/solicitud', 'SolicitudController@ver');
$router->post('/cliente/solicitud/agregar', 'SolicitudController@agregar');
$router->post('/cliente/solicitud/quitar', 'SolicitudController@quitar');
$router->post('/cliente/solicitud/fechas', 'SolicitudController@fechas');
$router->post('/cliente/solicitud/vaciar', 'SolicitudController@vaciar');
$router->post('/cliente/solicitud/enviar', 'SolicitudController@enviar');
$router->get('/cliente/alquileres', 'MisAlquileresController@index');
$router->get('/cliente/alquiler', 'MisAlquileresController@detalle');   // ?id=9
$router->post('/cliente/alquiler/cancelar', 'MisAlquileresController@cancelar');

// Administrador y Operador: clientes (RF-03). Sin /admin porque lo usan los dos roles.
$router->get('/clientes', 'GestionClienteController@index');
$router->post('/clientes/guardar', 'GestionClienteController@guardar');
$router->post('/clientes/actualizar', 'GestionClienteController@actualizar');
$router->post('/clientes/cambiar-estado', 'GestionClienteController@cambiarEstado');

// Administrador y Operador: operación de contratos (RF-06, RF-08)
$router->get('/solicitudes', 'ContratoController@solicitudes');
$router->post('/contratos/aprobar', 'ContratoController@aprobar');
$router->post('/contratos/rechazar', 'ContratoController@rechazar');
$router->get('/contratos', 'ContratoController@index');
$router->get('/contratos/detalle', 'ContratoController@detalle');   // ?id=9
$router->get('/contratos/nuevo', 'RegistroAlquilerController@nuevo');
$router->get('/contratos/nuevo/revisar', 'RegistroAlquilerController@revisar');
$router->post('/contratos/registrar', 'RegistroAlquilerController@registrar');

// Administrador y Operador: entregas (RF-09)
$router->get('/entregas', 'OperacionController@entregas');
$router->get('/entregas/registrar', 'OperacionController@formularioEntrega');   // ?id=9
$router->post('/entregas/guardar', 'OperacionController@guardarEntrega');

// Administrador: reportes (RF-14)
$router->get('/admin/reportes', 'ReporteController@index');
$router->get('/admin/reportes/equipo', 'ReporteController@equipo');   // ?id=4

// Administrador y Operador: mantenimiento y daños (RF-12)
$router->get('/mantenimiento', 'MantenimientoController@index');
$router->post('/mantenimiento/guardar', 'MantenimientoController@guardar');
$router->post('/mantenimiento/estado', 'MantenimientoController@estado');
$router->post('/mantenimiento/cerrar', 'MantenimientoController@cerrar');
$router->get('/danos', 'MantenimientoController@danos');

// Administrador y Operador: devoluciones y daños (RF-10, RF-11)
$router->get('/devoluciones', 'OperacionController@devoluciones');
$router->get('/devoluciones/registrar', 'OperacionController@formularioDevolucion');   // ?id=9
$router->post('/devoluciones/guardar', 'OperacionController@guardarDevolucion');
