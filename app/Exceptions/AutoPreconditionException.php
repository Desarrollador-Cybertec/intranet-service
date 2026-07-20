<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Se intentó escribir a mano una pre-condición que el sistema calcula solo
 * (antigüedad, capacitaciones). 422: la petición es válida pero no aplicable.
 */
class AutoPreconditionException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(422, $message);
    }
}
