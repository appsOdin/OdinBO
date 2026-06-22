<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ArticleController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;
use App\Controllers\VacationRequestController;
use App\Middleware\AuthMiddleware;

return [
    ['method' => 'GET', 'path' => '/', 'handler' => [AuthController::class, 'showLogin']],
    ['method' => 'GET', 'path' => '/login', 'handler' => [AuthController::class, 'showLogin']],
    ['method' => 'POST', 'path' => '/login', 'handler' => [AuthController::class, 'login']],
    ['method' => 'POST', 'path' => '/logout', 'handler' => [AuthController::class, 'logout'], 'middleware' => [AuthMiddleware::class]],

    ['method' => 'GET', 'path' => '/dashboard', 'handler' => [DashboardController::class, 'index'], 'middleware' => [AuthMiddleware::class]],

    ['method' => 'GET', 'path' => '/users', 'handler' => [UserController::class, 'index'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'path' => '/users/list', 'handler' => [UserController::class, 'list'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'path' => '/users/store', 'handler' => [UserController::class, 'store'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'path' => '/users/update', 'handler' => [UserController::class, 'update'], 'middleware' => [AuthMiddleware::class]],

    ['method' => 'GET', 'path' => '/articles', 'handler' => [ArticleController::class, 'index'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'path' => '/articles/list', 'handler' => [ArticleController::class, 'list'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'path' => '/articles/detail', 'handler' => [ArticleController::class, 'detail'], 'middleware' => [AuthMiddleware::class]],

    ['method' => 'GET', 'path' => '/rrhh/solicitud-vacaciones', 'handler' => [VacationRequestController::class, 'my'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'path' => '/rrhh/solicitud-vacaciones/crear', 'handler' => [VacationRequestController::class, 'create'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'path' => '/rrhh/solicitud-vacaciones/detalle', 'handler' => [VacationRequestController::class, 'detail'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'path' => '/rrhh/solicitud-vacaciones/store', 'handler' => [VacationRequestController::class, 'store'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'path' => '/rrhh/solicitud-vacaciones/save-signature', 'handler' => [VacationRequestController::class, 'saveSignature'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'path' => '/rrhh/solicitud-vacaciones/reject', 'handler' => [VacationRequestController::class, 'reject'], 'middleware' => [AuthMiddleware::class]],

    ['method' => 'GET', 'path' => '/rrhh/solicitudes-vacaciones', 'handler' => [VacationRequestController::class, 'all'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'path' => '/rrhh/solicitudes-vacaciones/signers', 'handler' => [VacationRequestController::class, 'signers'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'path' => '/rrhh/solicitudes-vacaciones/files', 'handler' => [VacationRequestController::class, 'files'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'path' => '/rrhh/solicitudes-vacaciones/add-signers', 'handler' => [VacationRequestController::class, 'addSigners'], 'middleware' => [AuthMiddleware::class]],

    ['method' => 'GET', 'path' => '/rrhh/solicitudes-para-firmar', 'handler' => [VacationRequestController::class, 'toSign'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'path' => '/rrhh/vacaciones/descargar', 'handler' => [VacationRequestController::class, 'downloadFile'], 'middleware' => [AuthMiddleware::class]],
];
