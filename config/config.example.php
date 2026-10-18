<?php
/**
 * EquipRent - Plantilla de configuración.
 *
 * Copia este archivo como config/config.local.php y escribe ahí los datos
 * reales. config.local.php está en .gitignore: nunca se sube al repositorio.
 */
return [
    'bd' => [
        'host'    => '127.0.0.1',
        'puerto'  => 3306,
        'nombre'  => 'equiprent',
        'charset' => 'utf8mb4',
        'usuario' => 'root',
        'clave'   => '',
    ],
    'app' => [
        'urlBase' => 'http://localhost/SWPADEPC2026/public',
        'debug'   => true,
    ],
];
