<?php

namespace Tests\Unit;

use App\Enums\TipoDeCupom;
use App\Excecoes\CupomEsgotadoException;
use App\Excecoes\CupomExpiradoException;
use App\Excecoes\GastoMinimoNaoAtingidoException;
use App\ObjetosDeValor\Cupom;
use App\Servicos\ServicoDeCupom;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ServicoDeCupomTest extends TestCase
{
    private ServicoDeCupom $servicoDeCupom;

    /**
     * Prepara o serviço e congela a data usada nos testes.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->servicoDeCupom = new ServicoDeCupom;
        Carbon::setTestNow('2026-11-03 12:00:00');
    }

    /**
     * Restaura o relógio para não afetar outros testes.
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Confirma o desconto percentual sobre o subtotal.
     */
    public function test_aplica_desconto_percentual(): void
    {
        // Arrange: cupom de 10% e carrinho de R$ 100,00
        $cupom = $this->criarCupom(tipo: TipoDeCupom::Percentual, valor: 10.0);

        // Act: aplica o cupom
        $totalComDesconto = $this->servicoDeCupom->aplicar($cupom, 100.0);

        // Assert: o total deve ser R$ 90,00
        $this->assertSame(90.0, $totalComDesconto);
    }

    /**
     * Confirma o desconto de valor fixo sobre o subtotal.
     */
    public function test_aplica_desconto_fixo(): void
    {
        // Arrange: cupom fixo de R$ 15,00
        $cupom = $this->criarCupom(tipo: TipoDeCupom::Fixo, valor: 15.0);

        // Act: aplica o cupom sobre R$ 100,00
        $totalComDesconto = $this->servicoDeCupom->aplicar($cupom, 100.0);

        // Assert: o total deve ser R$ 85,00
        $this->assertSame(85.0, $totalComDesconto);
    }

    /**
     * Rejeita um cupom cuja validade terminou antes de agora.
     */
    public function test_lanca_erro_quando_cupom_expirado(): void
    {
        // Arrange: cupom expirado ontem
        $cupom = $this->criarCupom(expiraEm: Carbon::now()->subDay());

        // Agir e Assert: a aplicação deve informar que expirou
        $this->expectException(CupomExpiradoException::class);
        $this->servicoDeCupom->aplicar($cupom, 100.0);
    }

    /**
     * Aceita um cupom até o instante exato de sua expiração.
     */
    public function test_cupom_ainda_vale_no_instante_da_expiracao(): void
    {
        // Arrange: expiração no mesmo instante do relógio congelado
        $cupom = $this->criarCupom(expiraEm: Carbon::now());

        // Act: aplica o cupom no instante da expiração
        $totalComDesconto = $this->servicoDeCupom->aplicar($cupom, 100.0);

        // Assert: o cupom ainda é válido e aplica 10%
        $this->assertSame(90.0, $totalComDesconto);
    }

    /**
     * Rejeita o subtotal quando ele fica abaixo do mínimo exigido.
     */
    public function test_lanca_erro_quando_gasto_minimo_nao_atingido(): void
    {
        // Arrange: cupom exige gasto mínimo de R$ 50,00
        $cupom = $this->criarCupom(gastoMinimo: 50.0);

        // Agir e Assert: R$ 30,00 não atingem o mínimo
        $this->expectException(GastoMinimoNaoAtingidoException::class);
        $this->servicoDeCupom->aplicar($cupom, 30.0);
    }

    /**
     * Aceita subtotal exatamente igual ao gasto mínimo.
     */
    public function test_aceita_subtotal_igual_ao_gasto_minimo(): void
    {
        // Arrange: subtotal e gasto mínimo de R$ 50,00
        $cupom = $this->criarCupom(gastoMinimo: 50.0);

        // Act: aplica o cupom no limite mínimo permitido
        $totalComDesconto = $this->servicoDeCupom->aplicar($cupom, 50.0);

        // Assert: o cupom é válido e desconta 10%
        $this->assertSame(45.0, $totalComDesconto);
    }

    /**
     * Rejeita um cupom que já atingiu o limite de usos.
     */
    public function test_lanca_erro_quando_limite_de_usos_atingido(): void
    {
        // Arrange: o cupom já foi usado duas vezes de um máximo de duas
        $cupom = $this->criarCupom(maximoDeUsos: 2, vezesUsado: 2);

        // Agir e Assert: novas aplicações devem ser rejeitadas
        $this->expectException(CupomEsgotadoException::class);
        $this->servicoDeCupom->aplicar($cupom, 100.0);
    }

    /**
     * Aceita usos ilimitados quando o limite não foi definido.
     */
    public function test_cupom_sem_limite_de_usos_sempre_vale(): void
    {
        // Arrange: sem limite definido e com 999 usos anteriores
        $cupom = $this->criarCupom(maximoDeUsos: null, vezesUsado: 999);

        // Act: aplica o cupom sem um limite de usos
        $totalComDesconto = $this->servicoDeCupom->aplicar($cupom, 100.0);

        // Assert: o cupom continua válido
        $this->assertSame(90.0, $totalComDesconto);
    }

    /**
     * Impede que um desconto fixo deixe o total abaixo de zero.
     */
    public function test_total_nunca_fica_negativo(): void
    {
        // Arrange: desconto fixo de R$ 50,00 sobre carrinho de R$ 10,00
        $cupom = $this->criarCupom(tipo: TipoDeCupom::Fixo, valor: 50.0);

        // Act: aplica o desconto maior que o subtotal
        $totalComDesconto = $this->servicoDeCupom->aplicar($cupom, 10.0);

        // Assert: o total mínimo é zero
        $this->assertSame(0.0, $totalComDesconto);
    }

    /**
     * Arredonda o total calculado para duas casas decimais.
     */
    public function test_arredonda_total_com_duas_casas_decimais(): void
    {
        // Arrange: percentual com resultado de três casas decimais
        $cupom = $this->criarCupom(tipo: TipoDeCupom::Percentual, valor: 12.345);

        // Act: aplica o desconto a R$ 100,00
        $totalComDesconto = $this->servicoDeCupom->aplicar($cupom, 100.0);

        // Assert: o resultado deve ser arredondado para R$ 87,66
        $this->assertSame(87.66, $totalComDesconto);
    }

    /**
     * Confirma vários cálculos com os mesmos passos de aplicação.
     */
    #[DataProvider('cenariosDeDesconto')]
    public function test_calcula_varios_cenarios(
        TipoDeCupom $tipo,
        float $valorDoCupom,
        float $subtotal,
        float $totalEsperado,
    ): void {
        // Arrange: monta o cupom com o tipo e o valor deste cenário
        $cupom = $this->criarCupom(tipo: $tipo, valor: $valorDoCupom);

        // Act: aplica o cupom ao subtotal do cenário
        $totalCalculado = $this->servicoDeCupom->aplicar($cupom, $subtotal);

        // Assert: o resultado corresponde ao valor esperado do cenário
        $this->assertSame($totalEsperado, $totalCalculado);
    }

    /**
     * Fornece valores conhecidos para testar diferentes tipos de desconto.
     *
     * @return array<string, array{TipoDeCupom, float, float, float}>
     */
    public static function cenariosDeDesconto(): array
    {
        return [
            'percentual 10% sobre 100' => [TipoDeCupom::Percentual, 10.0, 100.0, 90.0],
            'percentual 50% sobre 80' => [TipoDeCupom::Percentual, 50.0, 80.0, 40.0],
            'percentual 100% sobre 30' => [TipoDeCupom::Percentual, 100.0, 30.0, 0.0],
            'fixo 15 sobre 100' => [TipoDeCupom::Fixo, 15.0, 100.0, 85.0],
            'fixo 0 sobre 100' => [TipoDeCupom::Fixo, 0.0, 100.0, 100.0],
        ];
    }

    /**
     * Cria um cupom válido e permite personalizar os dados do cenário.
     */
    private function criarCupom(
        TipoDeCupom $tipo = TipoDeCupom::Percentual,
        float $valor = 10.0,
        ?CarbonInterface $expiraEm = null,
        ?float $gastoMinimo = null,
        ?int $maximoDeUsos = null,
        int $vezesUsado = 0,
    ): Cupom {
        return new Cupom(
            codigo: 'TESTE10',
            tipo: $tipo,
            valor: $valor,
            expiraEm: $expiraEm ?? Carbon::now()->addDay(),
            gastoMinimo: $gastoMinimo,
            maximoDeUsos: $maximoDeUsos,
            vezesUsado: $vezesUsado,
        );
    }
}
