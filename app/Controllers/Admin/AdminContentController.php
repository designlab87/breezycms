<?php

namespace App\Controllers\Admin;

use App\Core\Controller;

/**
 * Shared behaviour for admin content controllers.
 */
abstract class AdminContentController extends Controller
{
    /** Normalize a user-supplied string into a URL-safe slug. */
    protected function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-') ?: 'page';
    }
}
