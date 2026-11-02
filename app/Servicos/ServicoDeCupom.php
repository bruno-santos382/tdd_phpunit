<?php

namespace App\Servicos;

use App\ObjetosDeValor\Cupom;

/**
 * Aplica um cupom de desconto sobre o subtotal de um carrinho.
 */
class ServicoDeCupom
{
    /**
     * Aplica um cupom ao subtotal informado.
     */
    public function aplicar(Cupom $cupom, float $subtotal): float
    {
        throw new \LogicException('Ainda não implementado');
    }
}
