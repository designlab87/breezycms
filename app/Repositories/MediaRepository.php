<?php

namespace App\Repositories;

use App\Core\JsonStore;
use App\Core\UploadLimits;

/**
 * Media catalog. Uploaded binaries live under storage/uploads/{images,videos,
 * files}; metadata lives in storage/content/media/index.json keyed by id.
 */
class MediaRepository
{
    private string $contentDir;
    private string $indexFile;
    private string $uploadsDir;
    private array $uploadConfig;

    public function __construct(string $contentDir, string $uploadsDir, array $uploadConfig)
    {
        $this->contentDir = rtrim($contentDir, '/');
        $this->indexFile = $this->contentDir . '/media/index.json';
        $this->uploadsDir = rtrim($uploadsDir, '/');
        $this->uploadConfig = $uploadConfig;
    }

    /** @return array<string, array> media entries keyed by id */
    public function all(): array
    {
        return JsonStore::read($this->indexFile) ?? [];
    }

    /** @return array<int, array> entries of a single type, newest first */
    public function ofType(string $type): array
    {
        $items = [];
        foreach ($this->all() as $id => $entry) {
            if (($entry['type'] ?? '') === $type) {
                $entry['id'] = $id;
                $items[] = $entry;
            }
        }
        usort($items, fn ($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return $items;
    }

    public function find(string $id): ?array
    {
        $entry = $this->all()[$id] ?? null;
        if ($entry !== null) {
            $entry['id'] = $id;
        }
        return $entry;
    }

    /** Absolute path to the stored binary for an entry. */
    public function pathFor(array $entry): string
    {
        $dir = $this->uploadConfig[$entry['type']]['dir'] ?? 'files';
        return $this->uploadsDir . '/' . $dir . '/' . $entry['stored_name'];
    }

    /**
     * Validate and store an uploaded file.
     *
     * @throws \RuntimeException with a user-friendly message on failure.
     * @return array the created entry (including its 'id')
     */
    public function add(array $file, string $type): array
    {
        if (!isset($this->uploadConfig[$type])) {
            throw new \RuntimeException('Unknown media type.');
        }
        $rules = $this->uploadConfig[$type];

        if (!isset($file['error']) || is_array($file['error'])) {
            throw new \RuntimeException('Invalid upload.');
        }
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            throw new \RuntimeException('No file was selected.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->uploadErrorMessage((int) $file['error']));
        }

        $maxBytes = UploadLimits::maxBytes();
        if ($file['size'] > $maxBytes) {
            throw new \RuntimeException('File is too large (max ' . UploadLimits::humanSize($maxBytes) . ').');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $rules['extensions'], true)) {
            throw new \RuntimeException('File type .' . $ext . ' is not allowed for ' . $type . '.');
        }

        if (!empty($rules['mimes'])) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
            if (!in_array($mime, $rules['mimes'], true)) {
                throw new \RuntimeException('File content (' . $mime . ') does not match an allowed type.');
            }
        } else {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
        }

        $destDir = $this->uploadsDir . '/' . $rules['dir'];
        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $destDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            // Fallback for CLI/test contexts where move_uploaded_file is unavailable.
            if (!@rename($file['tmp_name'], $destPath)) {
                throw new \RuntimeException('Could not store the uploaded file.');
            }
        }
        @chmod($destPath, 0664);

        $id = 'm' . bin2hex(random_bytes(5));
        $entry = [
            'type'          => $type,
            'stored_name'   => $storedName,
            'original_name' => $file['name'],
            'mime'          => $mime,
            'size'          => (int) $file['size'],
            'created_at'    => gmdate('c'),
        ];

        $index = $this->all();
        $index[$id] = $entry;
        JsonStore::write($this->indexFile, $index);

        $entry['id'] = $id;
        return $entry;
    }

    public function delete(string $id): bool
    {
        $index = $this->all();
        if (!isset($index[$id])) {
            return false;
        }
        $path = $this->pathFor(array_merge($index[$id], ['id' => $id]));
        if (is_file($path)) {
            @unlink($path);
        }
        unset($index[$id]);
        JsonStore::write($this->indexFile, $index);
        return true;
    }

    /** True if the home page or any page references this media id in its layout. */
    public function isReferenced(string $id): bool
    {
        $home = JsonStore::read($this->contentDir . '/home.json');
        if (is_array($home) && $this->layoutReferencesMedia($home['layout'] ?? [], $id)) {
            return true;
        }

        foreach (JsonStore::listDir($this->contentDir . '/pages') as $doc) {
            if ($this->layoutReferencesMedia($doc['layout'] ?? [], $id)) {
                return true;
            }
            // Legacy template pages stored widgets under `content`.
            foreach ($doc['content'] ?? [] as $block) {
                $value = $block['value'] ?? null;
                if (is_array($value) && ($value['media_id'] ?? null) === $id) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Walk a builder layout tree for media_id / background_image references.
     *
     * @param array<int, mixed> $layout
     */
    private function layoutReferencesMedia(array $layout, string $id): bool
    {
        foreach ($layout as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (($item['background_image'] ?? null) === $id) {
                return true;
            }
            $bg = $item['background'] ?? null;
            if (is_array($bg) && ($bg['media_id'] ?? null) === $id) {
                return true;
            }
            foreach ($item['cols'] ?? [] as $col) {
                if (!is_array($col)) {
                    continue;
                }
                foreach ($col as $widget) {
                    if (!is_array($widget)) {
                        continue;
                    }
                    $value = $widget['value'] ?? null;
                    if (is_array($value) && ($value['media_id'] ?? null) === $id) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function uploadErrorMessage(int $code): string
    {
        $max = UploadLimits::humanSize(UploadLimits::maxBytes());
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload limit (' . $max . ').',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the server upload limit (' . $max . ').',
            UPLOAD_ERR_PARTIAL    => 'Upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder for uploads.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION  => 'A server extension blocked this upload.',
            default               => 'Upload failed (error code ' . $code . ').',
        };
    }
}
