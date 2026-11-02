<?php

namespace App\Enums;

/**
 * Tipos de desconto que um cupom pode ter.
 */
enum TipoDeCupom: string
{
    // Desconto em porcentagem. Exemplo: 10 significa 10%.
    case Percentual = 'percentual';

    // Desconto em valor fixo. Exemplo: 15 significa R$ 15,00.
    case Fixo = 'fixo';
}
