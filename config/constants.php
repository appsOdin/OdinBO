<?php

declare(strict_types=1);

// Load .env values for non-Docker executions.
$envFile = __DIR__ . '/../.env';
if (is_file($envFile) && is_readable($envFile)) {
	$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if (is_array($lines)) {
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || str_starts_with($line, '#')) {
				continue;
			}

			$separatorPos = strpos($line, '=');
			if ($separatorPos === false) {
				continue;
			}

			$key = trim(substr($line, 0, $separatorPos));
			$value = trim(substr($line, $separatorPos + 1));
			if ($key === '') {
				continue;
			}

			if (
				(str_starts_with($value, '"') && str_ends_with($value, '"'))
				|| (str_starts_with($value, "'") && str_ends_with($value, "'"))
			) {
				$value = substr($value, 1, -1);
			}

			if (getenv($key) === false) {
				putenv($key . '=' . $value);
				$_ENV[$key] = $value;
				$_SERVER[$key] = $value;
			}
		}
	}
}

// Helper: read env var or fall back to a default value.
// Allows Docker / CI to override without touching this file.
$_env = static function (string $key, mixed $default): mixed {
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
};

const APP_NAME = 'OdinBO';
define('APP_ENV',   $_env('APP_ENV',   'local'));
define('APP_DEBUG', filter_var($_env('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN));

define('APP_URL',      rtrim((string) $_env('APP_URL',      'http://localhost:8080/OdinBO/public'), '/'));
define('APP_TIMEZONE', (string) $_env('APP_TIMEZONE', 'America/Costa_Rica'));

define('API_BASE_URL',          rtrim((string) $_env('API_BASE_URL', 'http://localhost'), '/'));
define('TOKEN_REFRESH_MINUTES', (int) $_env('TOKEN_REFRESH_MINUTES', 5));
define('SESSION_TIMEOUT',       (int) $_env('SESSION_TIMEOUT',       60));

define('SESSION_NAME',    (string) $_env('SESSION_NAME',    'odinbo_session'));
const CSRF_TOKEN_KEY =  '_csrf_token';
define('CSRF_TOKEN_TTL', (int) $_env('CSRF_TOKEN_TTL', 1800));

define('HTTP_TIMEOUT_SECONDS', (int) $_env('HTTP_TIMEOUT_SECONDS', 20));
const LOG_FILE = __DIR__ . '/../storage/logs/app.log';

unset($_env);

const MENU_OPTIONS_ADMIN = [
	['label' => 'Dashboard', 'path' => 'dashboard'],
	['label' => 'Usuarios', 'path' => 'users'],
	['label' => 'Articulos', 'path' => 'articles'],
	['label' => 'Trace Logs', 'path' => 'admin/traces'],
	[
		'label' => 'RRHH',
		'children' => [
			['label' => 'Solicitud de vacaciones', 'path' => 'rrhh/solicitud-vacaciones'],
			['label' => 'Todas las solicitudes', 'path' => 'rrhh/solicitudes-vacaciones'],
			['label' => 'Para Firmar', 'path' => 'rrhh/solicitudes-para-firmar'],
		],
	],
];

const MENU_OPTIONS_USER = [
	['label' => 'Dashboard', 'path' => 'dashboard'],
	['label' => 'Articulos', 'path' => 'articles'],
	[
		'label' => 'RRHH',
		'children' => [
			['label' => 'Solicitud de vacaciones', 'path' => 'rrhh/solicitud-vacaciones'],
			['label' => 'Para Firmar', 'path' => 'rrhh/solicitudes-para-firmar'],
		],
	],
];

const MENU_OPTIONS_SUBSCRIBER = [
	['label' => 'Dashboard', 'path' => 'dashboard'],
	[
		'label' => 'RRHH',
		'children' => [
			['label' => 'Solicitud de vacaciones', 'path' => 'rrhh/solicitud-vacaciones'],
		],
	],
];
