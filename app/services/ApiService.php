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
     * @param array<string, mixed> $fields
     * @param array<string, array{name: string, tmp_name: string, type: string}> $files
     * @return array<string, mixed>
     */
    public function postMultipart(string $endpoint, array $fields, array $files = []): array
    {
        if ($this->tokenManager !== null) {
            $this->tokenManager->ensureFreshToken();
        }

        $baseUrl = rtrim(API_BASE_URL, '/');
        $normalizedEndpoint = '/' . ltrim($endpoint, '/');

        if (str_ends_with(strtolower($baseUrl), '/api') && str_starts_with(strtolower($normalizedEndpoint), '/api/')) {
            $normalizedEndpoint = substr($normalizedEndpoint, 4);
            $normalizedEndpoint = $normalizedEndpoint === '' ? '/' : $normalizedEndpoint;
        }

        $url = $baseUrl . $normalizedEndpoint;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Cannot initialize cURL');
        }

        $headers = ['Accept: application/json'];
        $token = $this->sessionManager->getToken();
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $multipartData = $fields;
        foreach ($files as $name => $file) {
            if (!isset($file['tmp_name'], $file['name']) || (string) $file['tmp_name'] === '') {
                continue;
            }

            $mime = (string) ($file['type'] ?? 'application/octet-stream');
            $multipartData[$name] = new \CURLFile((string) $file['tmp_name'], $mime, (string) $file['name']);
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => HTTP_TIMEOUT_SECONDS,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $multipartData,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $error !== '') {
            Logger::error('HTTP cURL multipart error', [
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
            Logger::error('Invalid JSON multipart response', [
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
            Logger::error('API multipart error response', [
                'url' => $url,
                'http_code' => $httpCode,
                'response' => $decoded,
            ]);
        }

        $decoded['http_code'] = $httpCode;

        return $decoded;
    }

    /**
     * Download a binary file from the API (e.g. PDF).
     *
     * @return array{http_code: int, body: string, content_type: string, content_disposition: string}
     */
    public function getFile(string $endpoint): array
    {
        if ($this->tokenManager !== null) {
            $this->tokenManager->ensureFreshToken();
        }

        $baseUrl = rtrim(API_BASE_URL, '/');
        $normalizedEndpoint = '/' . ltrim($endpoint, '/');

        if (str_ends_with(strtolower($baseUrl), '/api') && str_starts_with(strtolower($normalizedEndpoint), '/api/')) {
            $normalizedEndpoint = substr($normalizedEndpoint, 4);
            $normalizedEndpoint = $normalizedEndpoint === '' ? '/' : $normalizedEndpoint;
        }

        $url = $baseUrl . $normalizedEndpoint;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Cannot initialize cURL');
        }

        $headers = ['Accept: application/pdf, application/octet-stream, */*'];
        $token = $this->sessionManager->getToken();
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => HTTP_TIMEOUT_SECONDS,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false || $error !== '') {
            Logger::error('HTTP cURL getFile error', ['url' => $url, 'error' => $error]);
            return ['http_code' => 500, 'body' => '', 'content_type' => '', 'content_disposition' => ''];
        }

        $responseHeaders = substr((string) $rawResponse, 0, $headerSize);
        $body = substr((string) $rawResponse, $headerSize);
        $contentType = '';
        $contentDisposition = '';

        foreach (explode("\r\n", $responseHeaders) as $line) {
            $lower = strtolower($line);
            if (str_starts_with($lower, 'content-type:')) {
                $contentType = trim(substr($line, 13));
            } elseif (str_starts_with($lower, 'content-disposition:')) {
                $contentDisposition = trim(substr($line, 20));
            }
        }

        return [
            'http_code' => $httpCode,
            'body' => $body,
            'content_type' => $contentType,
            'content_disposition' => $contentDisposition,
        ];
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
