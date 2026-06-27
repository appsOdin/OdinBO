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
        $token = (string) ($userData['token'] ?? '');
        $_SESSION[self::KEY_AUTH] = [
            'id' => $userData['id'],
            'username' => $userData['username'],
            'token' => $token,
            'rolename' => $userData['rolename'],
            'expires_at' => $this->resolveExpiresAtFromToken($token),
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
        $_SESSION[self::KEY_AUTH]['expires_at'] = $this->resolveExpiresAtFromToken($token);
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

    private function resolveExpiresAtFromToken(string $token): int
    {
        $fallback = time() + (SESSION_TIMEOUT * 60);
        if ($token === '') {
            return $fallback;
        }

        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return $fallback;
        }

        $payload = strtr($parts[1], '-_', '+/');
        $padding = strlen($payload) % 4;
        if ($padding > 0) {
            $payload .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            return $fallback;
        }

        $claims = json_decode($decoded, true);
        if (!is_array($claims)) {
            return $fallback;
        }

        $exp = $claims['exp'] ?? null;
        if (!is_numeric($exp)) {
            return $fallback;
        }

        $expiresAt = (int) $exp;
        if ($expiresAt <= 0) {
            return $fallback;
        }

        return $expiresAt;
    }
}
