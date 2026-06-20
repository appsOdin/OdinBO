<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Centralized session state for authentication.
 */
final class SessionManager
{
    private const KEY_AUTH = 'auth';

    /**
     * @param array{id: string, username: string, token: string, rolename: string} $userData
     */
    public function storeAuth(array $userData): void
    {
        $_SESSION[self::KEY_AUTH] = [
            'id' => $userData['id'],
            'username' => $userData['username'],
            'token' => $userData['token'],
            'rolename' => $userData['rolename'],
            'expires_at' => time() + (SESSION_TIMEOUT * 60),
        ];
    }

    public function clearAuth(): void
    {
        unset($_SESSION[self::KEY_AUTH]);
    }

    public function isAuthenticated(): bool
    {
        return $this->getToken() !== null && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        $expiresAt = (int) ($_SESSION[self::KEY_AUTH]['expires_at'] ?? 0);
        return $expiresAt <= time();
    }

    public function getToken(): ?string
    {
        $token = $_SESSION[self::KEY_AUTH]['token'] ?? null;
        return is_string($token) && $token !== '' ? $token : null;
    }

    public function updateToken(string $token): void
    {
        if (!isset($_SESSION[self::KEY_AUTH])) {
            return;
        }

        $_SESSION[self::KEY_AUTH]['token'] = $token;
        $_SESSION[self::KEY_AUTH]['expires_at'] = time() + (SESSION_TIMEOUT * 60);
    }

    /**
     * @return array{id: string, username: string, rolename: string}|null
     */
    public function getUser(): ?array
    {
        if (!isset($_SESSION[self::KEY_AUTH])) {
            return null;
        }

        return [
            'id' => (string) ($_SESSION[self::KEY_AUTH]['id'] ?? ''),
            'username' => (string) ($_SESSION[self::KEY_AUTH]['username'] ?? ''),
            'rolename' => (string) ($_SESSION[self::KEY_AUTH]['rolename'] ?? ''),
        ];
    }

    public function getExpiresAt(): int
    {
        return (int) ($_SESSION[self::KEY_AUTH]['expires_at'] ?? 0);
    }
}
