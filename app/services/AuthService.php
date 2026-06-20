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
        $rolename = strtoupper(trim($this->extractRoleNameFromToken($token)));

        if ($rolename === '') {
            $rolename = 'USER';
        }

        if ($token === '' || $id === '' || $user === '') {
            return ['code' => '500', 'message' => 'Missing authentication data', 'data' => null];
        }

        $this->sessionManager->storeAuth([
            'id' => $id,
            'username' => $user,
            'token' => $token,
            'rolename' => $rolename,
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

    private function extractRoleNameFromToken(string $token): string
    {
        if ($token === '') {
            return '';
        }

        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return '';
        }

        $payload = strtr($parts[1], '-_', '+/');
        $padding = strlen($payload) % 4;
        if ($padding > 0) {
            $payload .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            return '';
        }

        $claims = json_decode($decoded, true);
        if (!is_array($claims)) {
            return '';
        }

        $role = $claims['rolename'] ?? ($claims['roleName'] ?? ($claims['RoleName'] ?? ''));
        return is_string($role) ? $role : '';
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
