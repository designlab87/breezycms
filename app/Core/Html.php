<?php

namespace App\Core;

/**
 * Lightweight HTML sanitizer for rich-text content produced by the editor.
 *
 * The only author is the trusted single admin, so this is defense-in-depth
 * rather than a hostile-input filter: it strips script/style/iframe/object
 * tags, event-handler attributes, and javascript: URLs while leaving normal
 * formatting markup intact.
 */
class Html
{
    public static function sanitize(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        // Remove dangerous elements (including their contents).
        $html = preg_replace('#<\s*(script|style|iframe|object|embed|form)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
        // Remove any stray opening/self-closing dangerous tags.
        $html = preg_replace('#<\s*/?\s*(script|style|iframe|object|embed|form)\b[^>]*>#i', '', $html);

        // Strip inline event handlers (onclick, onerror, ...).
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);

        // Neutralize javascript:/vbscript: URLs in href/src.
        $html = preg_replace('#\s(href|src)\s*=\s*("|\')\s*(javascript|vbscript):[^"\']*\2#i', ' $1=$2#$2', $html);

        return trim($html);
    }
}
