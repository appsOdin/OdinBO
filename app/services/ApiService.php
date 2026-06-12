<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Generic API service using cURL.
 */
final class ApiService
{
    public function __construct(
        private readonly SessionManager $sessionManager,
        private ?TokenManager $tokenManager = null
    ) {
    }

    public function setTokenManager(TokenManager $tokenManager): void
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = [], bool $autoRefresh = true): array
    {
        if ($query !== []) {
            $endpoint .= '?' . http_build_query($query);
        }

        return $this->request('GET', $endpoint, null, $autoRefresh);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $payload): array
    {
        return $this->request('POST', $endpoint, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function put(string $endpoint, array $payload): array
    {
        return $this->request('PUT', $endpoint, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function delete(string $endpoint, array $payload = []): array
    {
        return $this->request('DELETE', $endpoint, $payload);
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $endpoint, ?array $payload = null, bool $autoRefresh = true): array
    {
        if ($autoRefresh && $this->tokenManager !== null) {
            $this->tokenManager->ensureFreshToken();
        }

        $baseUrl = rtrim(API_BASE_URL, '/');
        $normalizedEndpoint = '/' . ltrim($endpoint, '/');

        // Prevent accidental /api/api duplication when base URL already ends in /api.
        if (str_ends_with(strtolower($baseUrl), '/api') && str_starts_with(strtolower($normalizedEndpoint), '/api/')) {
            $normalizedEndpoint = substr($normalizedEndpoint, 4);
            $normalizedEndpoint = $normalizedEndpoint === '' ? '/' : $normalizedEndpoint;
        }

        $url = $baseUrl . $normalizedEndpoint;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Cannot initialize cURL');
        }

        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        $token = $this->sessionManager->getToken();
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => HTTP_TIMEOUT_SECONDS,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $error !== '') {
            Logger::error('HTTP cURL error', [
                'method' => $method,
                'url' => $url,
                'error' => $error,
            ]);
            return [
                'code' => '500',
                'message' => 'HTTP communication error',
                'data' => null,
                'http_code' => 500,
            ];
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            Logger::error('Invalid JSON response', [
                'method' => $method,
                'url' => $url,
                'body' => $responseBody,
                'http_code' => $httpCode,
            ]);
            return [
                'code' => (string) $httpCode,
                'message' => 'Invalid API response',
                'data' => null,
                'http_code' => $httpCode,
            ];
        }

        if ($httpCode >= 400) {
            Logger::error('API error response', [
                'method' => $method,
                'url' => $url,
                'http_code' => $httpCode,
                'response' => $decoded,
            ]);
        }

        $decoded['http_code'] = $httpCode;

        return $decoded;
    }
}
