<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Refreshes JWT before expiration.
 */
final class TokenManager
{
    public function __construct(
        private readonly SessionManager $sessionManager,
        private readonly ApiService $apiService
    ) {
    }

    public function ensureFreshToken(): void
    {
        if (!$this->sessionManager->isAuthenticated()) {
            return;
        }

        $currentToken = $this->sessionManager->getToken();
        if ($currentToken === null || $currentToken === '') {
            return;
        }

        $secondsLeft = $this->sessionManager->getExpiresAt() - time();
        if ($secondsLeft > (TOKEN_REFRESH_MINUTES * 60)) {
            return;
        }

        $response = $this->apiService->get('/api/User/RefreshToken', [], false);
        $httpCode = (int) ($response['http_code'] ?? 0);
        $code = trim((string) ($response['code'] ?? ($response['Code'] ?? '')));

        if ($httpCode !== 200 || $code !== '200') {
            Logger::error('Token refresh failed', ['response' => $response]);
            return;
        }

        $data = $response['data'] ?? ($response['Data'] ?? []);
        $newToken = is_array($data)
            ? ($data['token'] ?? ($data['Token'] ?? null))
            : null;

        if (!is_string($newToken) || $newToken === '') {
            Logger::error('Token refresh failed', ['response' => $response]);
            return;
        }

        $this->sessionManager->updateToken($newToken);
    }
}
