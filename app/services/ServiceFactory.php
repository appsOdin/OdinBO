<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Creates service graph instances.
 */
final class ServiceFactory
{
    public static function sessionManager(): SessionManager
    {
        return new SessionManager();
    }

    public static function apiService(): ApiService
    {
        $session = self::sessionManager();
        $apiService = new ApiService($session);
        $tokenManager = new TokenManager($session, $apiService);
        $apiService->setTokenManager($tokenManager);

        return $apiService;
    }

    public static function authService(): AuthService
    {
        return new AuthService(self::apiService(), self::sessionManager());
    }

    public static function userService(): UserService
    {
        return new UserService(self::apiService());
    }
}
