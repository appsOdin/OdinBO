<?php

declare(strict_types=1);

const APP_NAME = 'OdinBO';
const APP_ENV = 'local';
const APP_DEBUG = true;

const APP_URL = 'http://localhost:8080/OdinBO/public';
const APP_TIMEZONE = 'America/Bogota';

const API_BASE_URL = 'http://localhost:5104';
const TOKEN_REFRESH_MINUTES = 5;
const SESSION_TIMEOUT = 60;

const SESSION_NAME = 'odinbo_session';
const CSRF_TOKEN_KEY = '_csrf_token';
const CSRF_TOKEN_TTL = 1800;

const HTTP_TIMEOUT_SECONDS = 20;
const LOG_FILE = __DIR__ . '/../storage/logs/app.log';

const MENU_OPTIONS_ADMIN = [
	['label' => 'Dashboard', 'path' => 'dashboard'],
	['label' => 'Usuarios', 'path' => 'users'],
	['label' => 'Articulos', 'path' => 'articles'],
];

const MENU_OPTIONS_USER = [
	['label' => 'Dashboard', 'path' => 'dashboard'],
	['label' => 'Articulos', 'path' => 'articles'],
];

const MENU_OPTIONS_SUBSCRIBER = [
	['label' => 'Dashboard', 'path' => 'dashboard'],
];
