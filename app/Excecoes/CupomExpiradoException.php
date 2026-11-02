<?php

namespace App\Excecoes;

use Exception;

/**
 * Erro lançado quando o cupom já passou da data de validade.
 */
class CupomExpiradoException extends Exception
{
    /**
     * Cria uma exceção com a mensagem de cupom expirado.
     */
    public function __construct()
    {
        parent::__construct('Este cupom está expirado.');
    }
}
