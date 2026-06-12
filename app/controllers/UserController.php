<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Helpers\Validator;
use App\Models\User;
use App\Services\ServiceFactory;

/**
 * User management controller.
 */
final class UserController extends Controller
{
    public function index(Request $request): void
    {
        $response = ServiceFactory::userService()->getAllUsers();
        $rows = is_array($response['data'] ?? null) ? $response['data'] : [];
        $users = array_map(static fn (array $row): User => User::fromArray($row), $rows);

        $this->view('users/index', [
            'title' => 'Usuarios',
            'users' => $users,
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }

    public function list(Request $request): void
    {
        $response = ServiceFactory::userService()->getAllUsers();
        $this->json($response);
    }

    public function store(Request $request): void
    {
        $payload = $this->sanitizePayload($request, false);

        if (!validate_csrf_token((string) ($payload['_csrf_token'] ?? ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido'], 403);
        }

        $errors = $this->validatePayload($payload, false);
        if ($errors !== []) {
            $this->json(['code' => '422', 'message' => implode(' ', $errors)], 422);
        }

        unset($payload['_csrf_token']);
        $response = ServiceFactory::userService()->createUser($payload);
        $this->json($response);
    }

    public function update(Request $request): void
    {
        $payload = $this->sanitizePayload($request, true);

        if (!validate_csrf_token((string) ($payload['_csrf_token'] ?? ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido'], 403);
        }

        $errors = $this->validatePayload($payload, true);
        if ($errors !== []) {
            $this->json(['code' => '422', 'message' => implode(' ', $errors)], 422);
        }

        $password = trim((string) ($payload['password'] ?? ''));
        if ($password === '') {
            unset($payload['password']);
        } else {
            $payload['password'] = $password;
        }

        unset($payload['_csrf_token']);
        $response = ServiceFactory::userService()->updateUser($payload);
        $this->json($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizePayload(Request $request, bool $isUpdate): array
    {
        return [
            '_csrf_token' => (string) $request->input('_csrf_token', ''),
            'id' => (string) $request->input('id', ''),
            'name' => sanitize_text((string) $request->input('name', '')),
            'lastname' => sanitize_text((string) $request->input('lastname', '')),
            'username' => sanitize_text((string) $request->input('username', '')),
            'password' => (string) $request->input('password', ''),
            'state' => (int) $request->input('state', 1),
            'islogin' => 0,
            'roleid' => (int) $request->input('roleid', 2),
            'email' => sanitize_email((string) $request->input('email', '')),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, string>
     */
    private function validatePayload(array $payload, bool $isUpdate): array
    {
        $validator = new Validator();
        $validator
            ->required('name', (string) $payload['name'], 'Nombre')
            ->required('lastname', (string) $payload['lastname'], 'Apellido')
            ->required('username', (string) $payload['username'], 'Usuario')
            ->required('email', (string) $payload['email'], 'Correo')
            ->email('email', (string) $payload['email'], 'Correo');

        if (!$isUpdate || ((string) $payload['password']) !== '') {
            $validator
                ->required('password', (string) $payload['password'], 'Contrasena')
                ->minLength('password', (string) $payload['password'], 8, 'Contrasena');
        }

        if ($isUpdate && (string) $payload['id'] === '') {
            return ['ID de usuario requerido para actualizar.'];
        }

        if ($validator->passes()) {
            return [];
        }

        return array_values($validator->errors());
    }
}
