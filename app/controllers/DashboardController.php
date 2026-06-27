<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\ServiceFactory;

/**
 * Dashboard controller.
 */
final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $userService = ServiceFactory::userService();
        $session = ServiceFactory::sessionManager();

        $usersResponse = $userService->getAllUsers();
        $apiHttpCode = (int) ($usersResponse['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($usersResponse['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $users = is_array($usersResponse['data'] ?? null) ? $usersResponse['data'] : [];

        $this->view('dashboard', [
            'title' => 'Dashboard',
            'authUser' => $session->getUser(),
            'totalUsers' => count($users),
            'today' => date('d/m/Y H:i'),
            'flashMessages' => consume_flash(),
        ]);
    }
}
