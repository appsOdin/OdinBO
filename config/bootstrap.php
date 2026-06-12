<?php

declare(strict_types=1);

require_once __DIR__ . '/constants.php';

use App\Core\Logger;

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

date_default_timezone_set(APP_TIMEZONE);

session_name(SESSION_NAME);
session_save_path(__DIR__ . '/../storage/sessions');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(static function (string $className): void {
    $prefix = 'App\\';
    if (!str_starts_with($className, $prefix)) {
        return;
    }

    $relativeClass = substr($className, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relativeClass) . '.php';
    $fullPath = __DIR__ . '/../app/' . $relativePath;

    if (file_exists($fullPath)) {
        require_once $fullPath;
    }
});

require_once __DIR__ . '/../app/helpers/common.php';

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
