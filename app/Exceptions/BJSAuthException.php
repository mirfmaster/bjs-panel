<?php

namespace App\Exceptions;

class BJSAuthException extends BJSException
{
    public function __construct(string $message = 'Authentication failed', int $code = BJSException::AUTH_FAILED)
    {
        parent::__construct($message, $code);
    }
}
