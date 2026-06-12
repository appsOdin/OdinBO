<?php

declare(strict_types=1);

use App\Core\Response;

if (!function_exists('detected_base_path')) {
    function detected_base_path(): string
    {
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = str_replace('\\', '/', dirname($scriptName));
        $basePath = $basePath === '.' ? '' : rtrim($basePath, '/');

        return $basePath === '/' ? '' : $basePath;
    }
}

if (!function_exists('detected_origin')) {
    function detected_origin(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $scheme . '://' . $host;
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        if (PHP_SAPI === 'cli') {
            return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
        }

        $base = detected_origin() . detected_base_path();
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header('Location: ' . base_url($path));
        exit;
    }
}

if (!function_exists('json_response')) {
    function json_response(array $payload, int $statusCode = 200): void
    {
        Response::json($payload, $statusCode);
    }
}

if (!function_exists('is_ajax_request')) {
    function is_ajax_request(): bool
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['CONTENT_TYPE']) && str_contains((string) $_SERVER['CONTENT_TYPE'], 'application/json'));
    }
}

if (!function_exists('sanitize_text')) {
    function sanitize_text(?string $value): string
    {
        return trim(filter_var((string) $value, FILTER_SANITIZE_SPECIAL_CHARS));
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email(?string $value): string
    {
        return trim((string) filter_var((string) $value, FILTER_SANITIZE_EMAIL));
    }
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_KEY] = [
            'value' => $token,
            'expires_at' => time() + CSRF_TOKEN_TTL,
        ];

        return $token;
    }
}

if (!function_exists('get_csrf_token')) {
    function get_csrf_token(): string
    {
        if (!isset($_SESSION[CSRF_TOKEN_KEY]) || !is_array($_SESSION[CSRF_TOKEN_KEY])) {
            return generate_csrf_token();
        }

        $tokenData = $_SESSION[CSRF_TOKEN_KEY];
        $expiresAt = (int) ($tokenData['expires_at'] ?? 0);

        if ($expiresAt < time()) {
            return generate_csrf_token();
        }

        return (string) ($tokenData['value'] ?? generate_csrf_token());
    }
}

if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token(?string $token): bool
    {
        $sessionToken = $_SESSION[CSRF_TOKEN_KEY]['value'] ?? null;
        $expiresAt = (int) ($_SESSION[CSRF_TOKEN_KEY]['expires_at'] ?? 0);

        return $sessionToken !== null
            && is_string($token)
            && hash_equals($sessionToken, $token)
            && $expiresAt >= time();
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        return htmlspecialchars((string) ($_SESSION['_old'][$key] ?? $default), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('with_old')) {
    function with_old(array $input): void
    {
        $_SESSION['_old'] = $input;
    }
}

if (!function_exists('clear_old')) {
    function clear_old(): void
    {
        unset($_SESSION['_old']);
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('consume_flash')) {
    function consume_flash(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return is_array($messages) ? $messages : [];
    }
}
