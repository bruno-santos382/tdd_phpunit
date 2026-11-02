<?php

namespace Tests\Feature;

use App\Enums\TipoDeCupom;
use App\ObjetosDeValor\Cupom;
use App\Servicos\ServicoDeCheckout;
use App\Servicos\ServicoDeCupom;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class CheckoutTest extends TestCase
{
    /**
     * Aplica o cupom e envia o total com desconto para o pagamento fictício.
     */
    public function test_envia_total_com_desconto_para_a_api_de_pagamento(): void
    {
        // Arrange: simula a aprovação do pagamento sem acessar a internet
        Http::preventStrayRequests();
        Http::fake([
            'https://pagamentos.exemplo.test/cobrar' => Http::response([
                'status' => 'aprovado',
            ], 201),
        ]);

        $cupom = new Cupom(
            codigo: 'TESTE10',
            tipo: TipoDeCupom::Percentual,
            valor: 10.0,
            expiraEm: Carbon::now()->addDay(),
        );
        $checkout = new ServicoDeCheckout(new ServicoDeCupom);

        // Act: finaliza o checkout de R$ 100,00 com o cupom
        $pagamentoAprovado = $checkout->finalizar($cupom, 100.0);

        // Assert: confirma o resultado do checkout
        $this->assertSame(true, $pagamentoAprovado);

        // Assert: confirma que somente uma cobrança recebeu o total de R$ 90,00
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $requisicao): bool => $requisicao->url() === 'https://pagamentos.exemplo.test/cobrar'
            && $requisicao->method() === 'POST'
            && $requisicao['codigo_cupom'] === 'TESTE10'
            && $requisicao['valor_total'] === 90.0);
    }
}
