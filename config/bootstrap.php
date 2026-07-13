<?php

declare(strict_types=1);

require_once __DIR__ . '/constants.php';

use App\Core\Logger;

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

date_default_timezone_set(APP_TIMEZONE);

require_once __DIR__ . '/../app/helpers/common.php';

$sessionLifetime = max(60, SESSION_TIMEOUT * 60);
// Keep a safe baseline so PHP GC does not remove session files before JWT expiration windows.
$sessionLifetime = max($sessionLifetime, 12 * 60 * 60);
ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
ini_set('session.cookie_lifetime', '0');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => is_https_request(),
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_name(SESSION_NAME);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// If auth expiration is already in session, align current request lifetime and session cookie to token exp.
$authExp = (int) ($_SESSION['auth']['expires_at'] ?? 0);
if ($authExp > time()) {
    $remaining = max(60, $authExp - time());
    $currentGc = (int) ini_get('session.gc_maxlifetime');
    if ($remaining > $currentGc) {
        ini_set('session.gc_maxlifetime', (string) $remaining);
    }

    if (!headers_sent() && session_id() !== '') {
        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires' => $authExp,
            'path' => (string) ($params['path'] ?? '/'),
            'domain' => (string) ($params['domain'] ?? ''),
            'secure' => is_https_request(),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => (string) ($params['samesite'] ?? 'Lax'),
        ]);
    }
}

spl_autoload_register(static function (string $className): void {
    $prefix = 'App\\';
    if (!str_starts_with($className, $prefix)) {
        return;
    }

    $relativeClass = substr($className, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

    // First try direct namespace-to-path mapping.
    $fullPath = __DIR__ . '/../app/' . $relativePath;
    if (file_exists($fullPath)) {
        require_once $fullPath;
        return;
    }

    // In this project, first-level app directories are lowercase (core, controllers, etc.).
    $parts = explode('/', $relativePath);
    if ($parts !== []) {
        $parts[0] = strtolower($parts[0]);
        $normalizedPath = __DIR__ . '/../app/' . implode('/', $parts);

        if (file_exists($normalizedPath)) {
            require_once $normalizedPath;
        }
    }
});

set_exception_handler(static function (Throwable $exception): void {
    Logger::error('Unhandled exception', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ]);

    http_response_code(500);

    if (is_ajax_request()) {
        json_response([
            'code' => '500',
            'message' => 'Internal server error',
        ], 500);
    }

    echo APP_DEBUG
        ? '<h3>Internal Server Error</h3><pre>' . htmlspecialchars($exception->getMessage()) . '</pre>'
        : '<h3>Internal Server Error</h3>';
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
