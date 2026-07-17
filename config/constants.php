<?php

declare(strict_types=1);

const APP_NAME = 'OdinBO';
define('APP_ENV', 'local');
define('APP_DEBUG', true);
define('APP_URL', 'http://localhost:8080/OdinBO/public');
define('APP_TIMEZONE', 'America/Costa_Rica');

$apiBaseUrl = getenv('API_BASE_URL');
define('API_BASE_URL', rtrim((is_string($apiBaseUrl) && $apiBaseUrl !== '') ? $apiBaseUrl : 'http://localhost:5104', '/'));
define('TOKEN_REFRESH_MINUTES', 5);
define('SESSION_TIMEOUT', 60);

define('SESSION_NAME', 'odinbo_session');
const CSRF_TOKEN_KEY =  '_csrf_token';
define('CSRF_TOKEN_TTL', 1800);

define('HTTP_TIMEOUT_SECONDS', 20);
const LOG_FILE = __DIR__ . '/../storage/logs/app.log';

const MENU_OPTIONS_SUPER = [
	['label' => 'Dashboard', 'path' => 'dashboard'],
	['label' => 'Cambiar contrasena', 'path' => 'users/change-password'],
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

const MENU_OPTIONS_ADMIN = [
	['label' => 'Dashboard', 'path' => 'dashboard'],
	['label' => 'Cambiar contrasena', 'path' => 'users/change-password'],
	['label' => 'Articulos', 'path' => 'articles'],
	[
		'label' => 'RRHH',
		'children' => [
			['label' => 'Todas las solicitudes', 'path' => 'rrhh/solicitudes-vacaciones'],
			['label' => 'Solicitud de vacaciones', 'path' => 'rrhh/solicitud-vacaciones'],
			['label' => 'Para Firmar', 'path' => 'rrhh/solicitudes-para-firmar'],
		],
	],
];

const MENU_OPTIONS_USER= [
	['label' => 'Dashboard', 'path' => 'dashboard'],
	['label' => 'Cambiar contrasena', 'path' => 'users/change-password'],
	['label' => 'Articulos', 'path' => 'articles'],
	[
		'label' => 'RRHH',
		'children' => [
			['label' => 'Solicitud de vacaciones', 'path' => 'rrhh/solicitud-vacaciones'],
			['label' => 'Para Firmar', 'path' => 'rrhh/solicitudes-para-firmar'],
		],
	],
];
