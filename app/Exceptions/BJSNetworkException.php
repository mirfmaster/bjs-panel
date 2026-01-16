<?php

namespace App\Exceptions;

class BJSNetworkException extends BJSException
{
    public function __construct(string $message = 'Network error occurred', int $code = BJSException::NETWORK_ERROR)
    {
        parent::__construct($message, $code);
    }
}
