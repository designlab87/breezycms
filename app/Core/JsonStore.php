<?php

namespace App\Core;

/**
 * Safe JSON file I/O. Writes are atomic (write to a temp file with an
 * exclusive lock, then rename over the target). Sufficient for a single-admin
 * editor with no concurrent writes.
 */
class JsonStore
{
    public static function read(string $file): ?array
    {
        if (!is_file($file)) {
            return null;
        }
        $raw = file_get_contents($file);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    public static function write(string $file, array $data): void
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON for ' . $file);
        }

        $tmp = tempnam($dir, 'js_');
        if ($tmp === false) {
            throw new \RuntimeException('Failed to create temp file in ' . $dir);
        }

        $fh = fopen($tmp, 'wb');
        if ($fh === false) {
            @unlink($tmp);
            throw new \RuntimeException('Failed to open temp file for writing');
        }

        flock($fh, LOCK_EX);
        fwrite($fh, $json);
        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);

        if (!rename($tmp, $file)) {
            @unlink($tmp);
            throw new \RuntimeException('Failed to move temp file into place: ' . $file);
        }

        @chmod($file, 0664);
    }

    public static function delete(string $file): bool
    {
        return is_file($file) ? unlink($file) : false;
    }

    /**
     * Read and decode every *.json file in a directory.
     *
     * @return array<string, array> keyed by filename without extension
     */
    public static function listDir(string $dir): array
    {
        $out = [];
        foreach (glob(rtrim($dir, '/') . '/*.json') ?: [] as $path) {
            $data = self::read($path);
            if ($data !== null) {
                $out[basename($path, '.json')] = $data;
            }
        }
        return $out;
    }
}
