<?php

namespace App\Exceptions;

use Exception;

class BJSException extends Exception
{
    public const AUTH_FAILED = 1001;
    public const SESSION_EXPIRED = 1002;
    public const NETWORK_ERROR = 1003;
    public const API_ERROR = 1004;

    public const CODE_SESSION_INVALID = 1001;
    public const CODE_AUTH_FAILED = 1002;
    public const CODE_API_ERROR = 1003;

    public static function sessionInvalid(string $message = 'Session is invalid'): self
    {
        return new self($message, self::CODE_SESSION_INVALID);
    }

    public static function authFailed(string $message = 'Authentication failed'): self
    {
        return new self($message, self::CODE_AUTH_FAILED);
    }

    public static function apiError(string $message, int $code = 0): self
    {
        return new self($message, self::CODE_API_ERROR + $code);
    }
}
