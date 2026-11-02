<?php

namespace App\ObjetosDeValor;

use App\Enums\TipoDeCupom;
use Carbon\CarbonInterface;

/**
 * Guarda os dados de um cupom de desconto sem acessar o banco de dados.
 */
final class Cupom
{
    /**
     * Cria um cupom imutável com suas regras e limites.
     */
    public function __construct(
        public readonly string $codigo,
        public readonly TipoDeCupom $tipo,
        public readonly float $valor,
        public readonly CarbonInterface $expiraEm,
        public readonly ?float $gastoMinimo = null,
        public readonly ?int $maximoDeUsos = null,
        public readonly int $vezesUsado = 0,
    ) {}
}
