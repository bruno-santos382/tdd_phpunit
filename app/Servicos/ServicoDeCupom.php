<?php

namespace App\Servicos;

use App\Enums\TipoDeCupom;
use App\Excecoes\CupomEsgotadoException;
use App\Excecoes\CupomExpiradoException;
use App\Excecoes\GastoMinimoNaoAtingidoException;
use App\ObjetosDeValor\Cupom;
use Carbon\Carbon;

/**
 * Aplica um cupom de desconto sobre o subtotal de um carrinho.
 */
class ServicoDeCupom
{
    /**
     * Valida o cupom e devolve o total já com desconto.
     */
    public function aplicar(Cupom $cupom, float $subtotal): float
    {
        if (Carbon::now()->greaterThan($cupom->expiraEm)) {
            throw new CupomExpiradoException;
        }

        if ($cupom->maximoDeUsos !== null && $cupom->vezesUsado >= $cupom->maximoDeUsos) {
            throw new CupomEsgotadoException;
        }

        if ($cupom->gastoMinimo !== null && $subtotal < $cupom->gastoMinimo) {
            throw new GastoMinimoNaoAtingidoException($cupom->gastoMinimo);
        }

        if ($cupom->tipo === TipoDeCupom::Percentual) {
            $valorDoDesconto = $subtotal * $cupom->valor / 100;
        } else {
            $valorDoDesconto = $cupom->valor;
        }

        return round(max(0.0, $subtotal - $valorDoDesconto), 2);
    }
}
