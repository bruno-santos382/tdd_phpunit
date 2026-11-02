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
        $this->validarExpiracao($cupom);
        $this->validarLimiteDeUsos($cupom);
        $this->validarGastoMinimo($cupom, $subtotal);

        return $this->calcularTotalComDesconto($cupom, $subtotal);
    }

    /**
     * Lança erro se o instante atual for posterior à expiração.
     */
    private function validarExpiracao(Cupom $cupom): void
    {
        // A igualdade ainda é válida; o cupom expira somente depois desse instante.
        if (Carbon::now()->greaterThan($cupom->expiraEm)) {
            throw new CupomExpiradoException;
        }
    }

    /**
     * Lança erro se o cupom já atingiu o limite de usos definido.
     */
    private function validarLimiteDeUsos(Cupom $cupom): void
    {
        if ($cupom->maximoDeUsos === null) {
            return;
        }

        if ($cupom->vezesUsado >= $cupom->maximoDeUsos) {
            throw new CupomEsgotadoException;
        }
    }

    /**
     * Lança erro se o subtotal não alcançar o gasto mínimo do cupom.
     */
    private function validarGastoMinimo(Cupom $cupom, float $subtotal): void
    {
        if ($cupom->gastoMinimo === null) {
            return;
        }

        if ($subtotal < $cupom->gastoMinimo) {
            throw new GastoMinimoNaoAtingidoException($cupom->gastoMinimo);
        }
    }

    /**
     * Calcula o total final, sem permitir resultado negativo.
     */
    private function calcularTotalComDesconto(Cupom $cupom, float $subtotal): float
    {
        if ($cupom->tipo === TipoDeCupom::Percentual) {
            $valorDoDesconto = $subtotal * $cupom->valor / 100;
        } else {
            $valorDoDesconto = $cupom->valor;
        }

        $totalComDesconto = max(0.0, $subtotal - $valorDoDesconto);

        return round($totalComDesconto, 2);
    }
}
