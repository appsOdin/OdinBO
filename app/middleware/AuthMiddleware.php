<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Services\SessionManager;

/**
 * Ensures authenticated access.
 */
final class AuthMiddleware
{
    public function handle(Request $request): void
    {
        $session = new SessionManager();
        if (!$session->isAuthenticated()) {
            flash('danger', 'Tu sesion ha expirado, inicia sesion nuevamente.');
            redirect('/login');
        }
    }
}
