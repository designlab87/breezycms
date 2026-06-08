<?php

namespace App\Core;

use App\Repositories\SettingsRepository;
use App\Repositories\UsersRepository;

/**
 * Handles both admin login (email + password, multi-account) and the visitor
 * "master password" gate. The two are stored and verified independently:
 * admins live in users.json; the visitor code hash lives in settings.json.
 *
 * Idle timeout: each context tracks a "last activity" timestamp. The `*LoggedIn`
 * / `gateOpen` checks expire the session once it exceeds the idle timeout, but
 * they do NOT refresh the timestamp. Only the `touch*` methods refresh it, and
 * those are called on real navigation/actions (never by the JS heartbeat) so
 * that polling cannot keep a session alive indefinitely.
 */
class Auth
{
    private SettingsRepository $settings;
    private UsersRepository $users;
    private int $idleTimeout;

    public function __construct(SettingsRepository $settings, UsersRepository $users, int $idleTimeout = 1800)
    {
        $this->settings = $settings;
        $this->users = $users;
        $this->idleTimeout = $idleTimeout;
    }

    // --- Admin -------------------------------------------------------------

    public function adminLoggedIn(): bool
    {
        if (empty($_SESSION['admin_authed'])) {
            return false;
        }
        if ($this->idleExpired('admin_last_activity')) {
            $this->logoutAdmin();
            return false;
        }
        return true;
    }

    public function attemptAdmin(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
        $hash = $user['password_hash'] ?? '';

        if (is_string($hash) && $hash !== '' && password_verify($password, $hash)) {
            session_regenerate_id(true);
            $_SESSION['admin_authed'] = true;
            $_SESSION['admin_email'] = $user['email'];
            $this->touchAdmin();
            return true;
        }
        return false;
    }

    /** Email of the currently logged-in admin, if any. */
    public function adminEmail(): ?string
    {
        return $_SESSION['admin_email'] ?? null;
    }

    /** Refresh the admin idle timer. Call on genuine activity only. */
    public function touchAdmin(): void
    {
        $_SESSION['admin_last_activity'] = time();
    }

    public function logoutAdmin(): void
    {
        unset($_SESSION['admin_authed'], $_SESSION['admin_last_activity'], $_SESSION['admin_email']);
    }

    // --- Visitor gate ------------------------------------------------------

    public function gateOpen(string $slug): bool
    {
        if (empty($_SESSION['gates'][$slug])) {
            return false;
        }
        if ($this->idleExpired('gate_last_activity')) {
            $this->closeGates();
            return false;
        }
        return true;
    }

    /**
     * Unlock a protected page. Each page carries its own bcrypt password hash.
     * For pages saved before per-page passwords existed (no hash yet) we fall
     * back to the legacy site-wide `visitor_gate_hash` so they keep working
     * until they're re-saved with their own password.
     */
    public function attemptGate(string $slug, string $password, ?string $pageHash = null): bool
    {
        $hash = ($pageHash !== null && $pageHash !== '')
            ? $pageHash
            : $this->settings->get('visitor_gate_hash');

        if (is_string($hash) && $hash !== '' && password_verify($password, $hash)) {
            $_SESSION['gates'][$slug] = true;
            $this->touchGate();
            return true;
        }
        return false;
    }

    /** Refresh the visitor gate idle timer. Call on genuine activity only. */
    public function touchGate(): void
    {
        $_SESSION['gate_last_activity'] = time();
    }

    public function closeGates(): void
    {
        unset($_SESSION['gates'], $_SESSION['gate_last_activity']);
    }

    // --- Internal ----------------------------------------------------------

    /**
     * True if the given last-activity timestamp is older than the idle timeout.
     * A missing timestamp is treated as "not expired" so the clock starts on
     * the next activity (avoids surprise logout for pre-existing sessions).
     */
    private function idleExpired(string $key): bool
    {
        if ($this->idleTimeout <= 0) {
            return false;
        }
        $last = $_SESSION[$key] ?? null;
        if ($last === null) {
            return false;
        }
        return (time() - (int) $last) > $this->idleTimeout;
    }
}
