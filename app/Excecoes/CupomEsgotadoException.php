<?php

namespace App\Excecoes;

use Exception;

/**
 * Erro lançado quando o cupom atingiu o limite de usos.
 */
class CupomEsgotadoException extends Exception
{
    /**
     * Cria uma exceção com a mensagem de limite de usos atingido.
     */
    public function __construct()
    {
        parent::__construct('Este cupom atingiu o limite de usos.');
    }
}
