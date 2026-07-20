<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * El participante no cumple las pre-condiciones de Súmate.
 * 422: la petición es válida pero el estado del recurso no la permite.
 */
class NotEligibleException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(422, $message);
    }
}
