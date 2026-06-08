<?php

namespace App\Core;

/**
 * Effective upload size limits from PHP ini (upload_max_filesize, post_max_size).
 * The Media Library displays and enforces whatever PHP allows on this server.
 */
class UploadLimits
{
    /** Largest single-file upload PHP will accept (bytes). */
    public static function maxBytes(): int
    {
        $upload = self::parseIniSize((string) ini_get('upload_max_filesize'));
        $post   = self::parseIniSize((string) ini_get('post_max_size'));
        return min($upload, $post);
    }

    /** Parse a PHP ini size value (e.g. "2M", "128M", "1G") to bytes. */
    public static function parseIniSize(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return PHP_INT_MAX;
        }
        $unit = strtolower(substr($value, -1));
        $num  = (float) $value;
        return match ($unit) {
            'g'     => (int) ($num * 1024 * 1024 * 1024),
            'm'     => (int) ($num * 1024 * 1024),
            'k'     => (int) ($num * 1024),
            default => (int) $num,
        };
    }

    public static function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }
        return rtrim(rtrim(number_format($n, 1), '0'), '.') . ' ' . $units[$i];
    }
}
