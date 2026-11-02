# Sistema de Cupom de Desconto

Projeto didático para demonstrar testes unitários com PHPUnit e o ciclo
TDD: **Red**, **Green** e **Refactor**. Em **Red**, escrevemos um teste para
uma regra ainda não implementada e confirmamos que ele falha. Em **Green**,
implementamos o mínimo necessário para o teste passar. Em **Refactor**,
melhoramos a organização do código sem mudar o comportamento; os testes devem
continuar passando.

## Requisitos

- PHP 8.4.1 ou superior
- Composer

## Instalação e testes

```bash
composer install
php artisan test
```

Para executar somente os testes unitários:

```bash
php artisan test --testsuite=Unit
```

Outras opções:

| Comando | Para que serve |
| --- | --- |
| `php artisan test` | Executa toda a suíte de testes. |
| `php artisan test tests/Unit/ServicoDeCupomTest.php` | Executa um arquivo de teste específico. |
| `php artisan test --filter=aplica_desconto_percentual` | Executa testes cujo nome corresponde ao filtro. |
| `php artisan test --testsuite=Unit` | Executa apenas a suíte Unit (use `Feature` para testes de funcionalidade). |
| `php artisan test --stop-on-failure` | Interrompe a execução ao primeiro teste que falhar. |
| `php artisan test --coverage` | Gera relatório de cobertura de código (requer driver de cobertura, como Xdebug ou PCOV). |
| `vendor/bin/phpunit` | Executa o PHPUnit diretamente, sem a camada de saída do Artisan. |

> **Dica:** acrescente `--help` a `php artisan test` para consultar as opções disponíveis na versão instalada do Laravel.


## Regras do cupom

O serviço verifica, nesta ordem, expiração, limite de usos e gasto mínimo.
Depois calcula o desconto percentual ou fixo, limita o total a zero e arredonda
o resultado para duas casas decimais. Um cupom expirando exatamente agora e um
subtotal igual ao gasto mínimo ainda são válidos.

## O que cada teste verifica

Os testes ficam em [`tests/Unit/ServicoDeCupomTest.php`](tests/Unit/ServicoDeCupomTest.php).

- `test_aplica_desconto_percentual`: um cupom de 10% reduz um subtotal de R$ 100,00 para R$ 90,00.
- `test_aplica_desconto_fixo`: um cupom de R$ 15,00 reduz um subtotal de R$ 100,00 para R$ 85,00.
- `test_lanca_erro_quando_cupom_expirado`: lança `CupomExpiradoException` quando a validade terminou antes do momento atual.
- `test_cupom_ainda_vale_no_instante_da_expiracao`: aceita o cupom quando a expiração é exatamente igual ao momento atual.
- `test_lanca_erro_quando_gasto_minimo_nao_atingido`: lança `GastoMinimoNaoAtingidoException` quando o subtotal fica abaixo do mínimo.
- `test_aceita_subtotal_igual_ao_gasto_minimo`: aceita o cupom quando o subtotal é exatamente igual ao mínimo.
- `test_lanca_erro_quando_limite_de_usos_atingido`: lança `CupomEsgotadoException` quando os usos já alcançaram o limite.
- `test_cupom_sem_limite_de_usos_sempre_vale`: aceita o cupom mesmo após muitos usos quando não há limite definido.
- `test_total_nunca_fica_negativo`: limita o total a zero quando o desconto fixo é maior que o subtotal.
- `test_arredonda_total_com_duas_casas_decimais`: arredonda o resultado para duas casas decimais.
- `test_calcula_varios_cenarios`: usa um **Data Provider** para verificar cinco combinações de desconto:
  - 10% sobre R$ 100,00 resulta em R$ 90,00.
  - 50% sobre R$ 80,00 resulta em R$ 40,00.
  - 100% sobre R$ 30,00 resulta em R$ 0,00.
  - Desconto fixo de R$ 15,00 sobre R$ 100,00 resulta em R$ 85,00.
  - Desconto fixo de R$ 0,00 sobre R$ 100,00 mantém o total em R$ 100,00.

O Data Provider executa o mesmo teste para cada combinação. Por isso, os 11
métodos de teste resultam em 15 execuções no total.

## Teste de feature do checkout

O teste fica em [`tests/Feature/CheckoutTest.php`](tests/Feature/CheckoutTest.php).

- `test_envia_total_com_desconto_para_a_api_de_pagamento`: cria um cupom de 10%,
  aplica o desconto a um subtotal de R$ 100,00 e usa `Http::fake()` para simular
  a API externa de pagamento. Verifica que o checkout foi aprovado e que uma
  única cobrança foi enviada com o código do cupom e o total de R$ 90,00.

Esse teste exercita o serviço de cupom junto com o checkout, sem fazer uma
conexão real à internet nem persistir dados em banco.
