<?php

declare(strict_types=1);

const APP_NAME = 'OdinBO';
define('APP_ENV', 'local');
define('APP_DEBUG', true);
define('APP_URL', 'http://localhost:8181/OdinBO/public');
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

const HOLIDAYS = [
    '01-01', // Año Nuevo
    '04-02', // Jueves Santo (variable, este formato no aplica)
    '04-03', // Viernes Santo (variable, este formato no aplica)
    '04-11', // Juan Santamaría
    '05-01', // Día del Trabajador
    '07-25', // Anexión del Partido de Nicoya
    '08-15', // Día de la Madre
    '09-15', // Independencia
    '12-25', // Navidad
    "08-02", // Día de la Virgen de los Ángeles
    "08-31", // Día de la Persona Negra y la Cultura Afrocostarricense
    "12-01", // Abolición del Ejército
];

