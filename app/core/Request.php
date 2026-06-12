<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP request wrapper.
 */
final class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = str_replace('\\', '/', dirname($scriptName));
        $basePath = $basePath === '.' ? '' : rtrim($basePath, '/');
        if ($basePath === '/') {
            $basePath = '';
        }

        if ($basePath !== '' && str_starts_with((string) $uri, $basePath . '/')) {
            $uri = substr((string) $uri, strlen($basePath));
        } elseif ($basePath !== '' && (string) $uri === $basePath) {
            $uri = '/';
        }

        $path = '/' . ltrim((string) $uri, '/');
        return $path === '' ? '/' : (rtrim($path, '/') ?: '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->isJson()) {
            $raw = file_get_contents('php://input') ?: '{}';
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $input = $this->all();
        return $input[$key] ?? $_GET[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function isJson(): bool
    {
        return isset($_SERVER['CONTENT_TYPE']) && str_contains((string) $_SERVER['CONTENT_TYPE'], 'application/json');
    }
}
