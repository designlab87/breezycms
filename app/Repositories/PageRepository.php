<?php

namespace App\Repositories;

use App\Core\JsonStore;

/**
 * Pages stored one JSON file per slug: storage/content/pages/{slug}.json
 */
class PageRepository
{
    private string $dir;
    private string $homeFile;

    public function __construct(string $contentDir)
    {
        $contentDir = rtrim($contentDir, '/');
        $this->dir = $contentDir . '/pages';
        $this->homeFile = $contentDir . '/home.json';
    }

    /**
     * The home page is a special builder page (not deletable / protectable /
     * draftable and not listed among normal pages). It lives in its own file.
     *
     * @return array{layout: array}
     */
    public function home(): array
    {
        $data = JsonStore::read($this->homeFile) ?? [];
        $data['layout'] = $data['layout'] ?? [];
        return $data;
    }

    public function saveHome(array $layout): void
    {
        JsonStore::write($this->homeFile, [
            'layout'     => $layout,
            'updated_at' => gmdate('c'),
        ]);
    }

    /**
     * Persist a new menu/sort order from an ordered list of slugs. Pages not
     * listed keep a stable position after the listed ones.
     */
    public function setOrder(array $slugs): void
    {
        $i = 0;
        foreach ($slugs as $slug) {
            $page = $this->findBySlug((string) $slug);
            if ($page) {
                $page['sort'] = $i++;
                JsonStore::write($this->file($page['slug']), $page);
            }
        }
    }

    /** @return array<int, array> all pages sorted by sort then title */
    public function all(): array
    {
        $pages = array_values(JsonStore::listDir($this->dir));
        usort($pages, function ($a, $b) {
            $s = ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
            return $s !== 0 ? $s : strcasecmp($a['title'] ?? '', $b['title'] ?? '');
        });
        return $pages;
    }

    /** @return array<int, array> published pages only */
    public function published(): array
    {
        return array_values(array_filter($this->all(), fn ($p) => ($p['status'] ?? '') === 'published'));
    }

    /**
     * Published pages that should appear in the site navigation. Pages missing
     * the `in_menu` flag (e.g. older data) default to visible.
     *
     * @return array<int, array>
     */
    public function menu(): array
    {
        return array_values(array_filter($this->published(), fn ($p) => ($p['in_menu'] ?? true) !== false));
    }

    public function findBySlug(string $slug): ?array
    {
        return JsonStore::read($this->file($slug));
    }

    public function slugExists(string $slug, ?string $exceptSlug = null): bool
    {
        if ($exceptSlug !== null && $slug === $exceptSlug) {
            return false;
        }
        return is_file($this->file($slug));
    }

    public function create(array $data): array
    {
        $now = gmdate('c');
        $data['id'] = $data['id'] ?? bin2hex(random_bytes(4));
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        JsonStore::write($this->file($data['slug']), $data);
        return $data;
    }

    /**
     * Update a page. If the slug changed, the old file is removed and a new one
     * written under the new slug.
     */
    public function update(string $oldSlug, array $data): array
    {
        $data['updated_at'] = gmdate('c');
        JsonStore::write($this->file($data['slug']), $data);

        if ($oldSlug !== $data['slug']) {
            JsonStore::delete($this->file($oldSlug));
        }
        return $data;
    }

    public function delete(string $slug): bool
    {
        return JsonStore::delete($this->file($slug));
    }

    private function file(string $slug): string
    {
        return $this->dir . '/' . $slug . '.json';
    }
}
