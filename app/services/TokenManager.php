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

        $secondsLeft = $this->sessionManager->getExpiresAt() - time();
        if ($secondsLeft > (TOKEN_REFRESH_MINUTES * 60)) {
            return;
        }

        $response = $this->apiService->get('/api/User/RefreshToken', [], false);
        $newToken = $response['data']['token'] ?? null;

        if (!is_string($newToken) || $newToken === '') {
            Logger::error('Token refresh failed', ['response' => $response]);
            return;
        }

        $this->sessionManager->updateToken($newToken);
    }
}
