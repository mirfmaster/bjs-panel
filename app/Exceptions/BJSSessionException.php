<?php

namespace App\Exceptions;

class BJSSessionException extends BJSException
{
    public function __construct(string $message = 'Session expired or re-authentication failed', int $code = BJSException::SESSION_EXPIRED)
    {
        parent::__construct($message, $code);
    }
}
