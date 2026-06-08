<?php

namespace App\Repositories;

use App\Core\JsonStore;

/**
 * Admin accounts stored as a flat list in storage/content/users.json.
 *
 * The structure supports multiple accounts. To add one by hand, append an
 * object with `email`, `name`, and a `password_hash` (generate the hash with
 * PHP's password_hash(), e.g. `php -r 'echo password_hash("secret", PASSWORD_DEFAULT);'`).
 *
 * Login is by email (case-insensitive) + password.
 */
class UsersRepository
{
    private string $file;

    public function __construct(string $contentDir)
    {
        $this->file = rtrim($contentDir, '/') . '/users.json';
    }

    /** @return array<int, array> all accounts */
    public function all(): array
    {
        $data = JsonStore::read($this->file);
        return is_array($data) ? array_values($data) : [];
    }

    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }
        foreach ($this->all() as $user) {
            if (strtolower(trim((string) ($user['email'] ?? ''))) === $email) {
                return $user;
            }
        }
        return null;
    }

    public function count(): int
    {
        return count($this->all());
    }

    /** Update the password hash for the account with the given email. */
    public function updatePassword(string $email, string $hash): bool
    {
        $email = strtolower(trim($email));
        $users = $this->all();
        $changed = false;

        foreach ($users as &$user) {
            if (strtolower(trim((string) ($user['email'] ?? ''))) === $email) {
                $user['password_hash'] = $hash;
                $user['updated_at'] = gmdate('c');
                $changed = true;
                break;
            }
        }
        unset($user);

        if ($changed) {
            JsonStore::write($this->file, $users);
        }
        return $changed;
    }
}
