<?php
/**
 * EquipRent - Error HTTP controlado (404, 403...). El front controller
 * lo atrapa y muestra la página correspondiente.
 */
declare(strict_types=1);

class ErrorHttp extends RuntimeException
{
}
