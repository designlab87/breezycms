<?php

namespace App\Core;

/**
 * Per-session CSRF token management.
 */
class Csrf
{
    private const KEY = '_csrf_token';

    public function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public function verify(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION[self::KEY])
            && hash_equals($_SESSION[self::KEY], $token);
    }

    /** Hidden input markup for embedding in forms. */
    public function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e($this->token()) . '">';
    }
}
