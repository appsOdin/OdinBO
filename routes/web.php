<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ArticleController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;
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
];
