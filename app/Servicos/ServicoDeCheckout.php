<?php

namespace App\Servicos;

use App\ObjetosDeValor\Cupom;
use Illuminate\Support\Facades\Http;

class ServicoDeCheckout
{
    /**
     * Cria o checkout com o serviço que valida e calcula cupons.
     */
    public function __construct(private ServicoDeCupom $servicoDeCupom) {}

    /**
     * Aplica o cupom e envia o total para a API fictícia de pagamento.
     */
    public function finalizar(Cupom $cupom, float $subtotal): bool
    {
        $totalComDesconto = $this->servicoDeCupom->aplicar($cupom, $subtotal);

        $resposta = Http::acceptJson()->post('https://pagamentos.exemplo.test/cobrar', [
            'codigo_cupom' => $cupom->codigo,
            'valor_total' => $totalComDesconto,
        ]);

        return $resposta->successful();
    }
}
