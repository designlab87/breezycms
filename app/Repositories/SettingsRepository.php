<?php

namespace App\Repositories;

use App\Core\JsonStore;

/**
 * Site-wide key/value settings stored in a single JSON file. Holds the hashed
 * admin and visitor passwords plus general site config.
 */
class SettingsRepository
{
    private string $file;
    private ?array $cache = null;

    public function __construct(string $contentDir)
    {
        $this->file = rtrim($contentDir, '/') . '/settings.json';
    }

    public function all(): array
    {
        if ($this->cache === null) {
            $this->cache = JsonStore::read($this->file) ?? [];
        }
        return $this->cache;
    }

    public function get(string $key, $default = null)
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $data = $this->all();
        $data[$key] = $value;
        JsonStore::write($this->file, $data);
        $this->cache = $data;
    }

    public function setMany(array $values): void
    {
        $data = array_merge($this->all(), $values);
        JsonStore::write($this->file, $data);
        $this->cache = $data;
    }
}
