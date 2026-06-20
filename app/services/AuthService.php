<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Handles authentication use-cases.
 */
final class AuthService
{
    public function __construct(
        private readonly ApiService $apiService,
        private readonly SessionManager $sessionManager
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function login(string $username, string $password): array
    {
        $response = $this->apiService->post('/api/User/Login', [
            'username' => $username,
            'password' => $password,
        ]);

        if (!$this->isSuccessResponse($response)) {
            return $response;
        }

        $data = $response['data'] ?? ($response['Data'] ?? []);
        if (!is_array($data)) {
            return ['code' => '500', 'message' => 'Invalid login payload', 'data' => null];
        }

        $token = (string) ($data['token'] ?? ($data['Token'] ?? ''));
        $id = (string) ($data['id'] ?? ($data['Id'] ?? ''));
        $user = (string) ($data['username'] ?? ($data['userName'] ?? ($data['Username'] ?? '')));

        if ($token === '' || $id === '' || $user === '') {
            return ['code' => '500', 'message' => 'Missing authentication data', 'data' => null];
        }

        $this->sessionManager->storeAuth([
            'id' => $id,
            'username' => $user,
            'token' => $token,
        ]);

        return $response;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function isSuccessResponse(array $response): bool
    {
        $httpCode = (int) ($response['http_code'] ?? 0);
        $rawCode = $response['code'] ?? ($response['Code'] ?? null);
        $code = trim((string) $rawCode);

        return $httpCode === 200 && $code === '200';
    }

    public function logout(): void
    {
        $token = $this->sessionManager->getToken();

        if ($token === null || $token === '') {
            Logger::error('Logout requested without bearer token in session');
            $this->sessionManager->clearAuth();
            return;
        }

        // ApiService sends Authorization: Bearer <token> automatically using SessionManager token.
        $response = $this->apiService->post('/api/User/Logout', []);
        $httpCode = (int) ($response['http_code'] ?? 0);

        if ($httpCode >= 400) {
            Logger::error('Logout API failed', ['response' => $response]);
        }

        $this->sessionManager->clearAuth();
    }
}
