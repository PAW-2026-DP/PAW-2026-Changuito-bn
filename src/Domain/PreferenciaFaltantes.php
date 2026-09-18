<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Qué hacer cuando un producto de la lista no está disponible en ninguno
 * de los comercios considerados por el motor de optimización.
 */
enum PreferenciaFaltantes: string
{
    case EXCLUIR = 'excluir';
    case SUSTITUIR = 'sustituir';
    case DESCARTAR_SI_SUPERA_UMBRAL = 'descartar_si_supera_umbral';
}
