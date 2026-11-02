<?php

namespace App\Excecoes;

use Exception;

/**
 * Erro lançado quando o subtotal fica abaixo do gasto mínimo.
 */
class GastoMinimoNaoAtingidoException extends Exception
{
    /**
     * Cria uma exceção com o valor mínimo exigido pelo cupom.
     */
    public function __construct(float $gastoMinimo)
    {
        $gastoMinimoFormatado = number_format($gastoMinimo, 2, ',', '.');

        parent::__construct("O gasto mínimo para este cupom é de R$ {$gastoMinimoFormatado}.");
    }
}
