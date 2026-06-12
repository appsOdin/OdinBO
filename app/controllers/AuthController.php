<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Helpers\Validator;
use App\Services\ServiceFactory;

/**
 * Authentication controller.
 */
final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        $sessionManager = ServiceFactory::sessionManager();
        if ($sessionManager->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $this->view('login', [
            'title' => 'Iniciar sesion',
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ], null);
    }

    public function login(Request $request): void
    {
        $username = sanitize_text((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');
        $csrf = (string) $request->input('_csrf_token', '');

        if (!validate_csrf_token($csrf)) {
            flash('danger', 'Token CSRF invalido.');
            with_old(['username' => $username]);
            $this->redirect('/login');
        }

        $validator = new Validator();
        $validator
            ->required('username', $username, 'Usuario')
            ->required('password', $password, 'Contrasena');

        if (!$validator->passes()) {
            flash('danger', implode(' ', $validator->errors()));
            with_old(['username' => $username]);
            $this->redirect('/login');
        }

        $service = ServiceFactory::authService();
        $response = $service->login($username, $password);

        $httpCode = (int) ($response['http_code'] ?? 0);
        $code = trim((string) ($response['code'] ?? ''));
        if ($httpCode !== 200 || $code !== '200') {
            flash('danger', (string) ($response['message'] ?? 'No fue posible iniciar sesion.'));
            with_old(['username' => $username]);
            $this->redirect('/login');
        }

        clear_old();
        flash('success', 'Bienvenido al sistema.');
        $this->redirect('/dashboard');
    }

    public function logout(Request $request): void
    {
        $csrf = (string) $request->input('_csrf_token', '');
        if (!validate_csrf_token($csrf)) {
            flash('danger', 'Token CSRF invalido.');
            $this->redirect('/dashboard');
        }

        ServiceFactory::authService()->logout();
        flash('success', 'Sesion cerrada correctamente.');
        $this->redirect('/login');
    }
}
