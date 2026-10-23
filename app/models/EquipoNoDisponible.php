<?php
/**
 * EquipRent - Un equipo de la solicitud ya no está libre (otra persona lo reservó
 * primero). La lanza Solicitud::enviar() después del rollback; su mensaje ya dice
 * qué equipo es y qué hacer.
 */
declare(strict_types=1);

class EquipoNoDisponible extends RuntimeException
{
}
