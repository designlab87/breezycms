<?php

if (!function_exists('e')) {
    /**
     * Escape a value for safe HTML output.
     */
    function e($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
