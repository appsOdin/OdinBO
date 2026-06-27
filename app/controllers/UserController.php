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
        $apiHttpCode = (int) ($response['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('login');
            return;
        }

        $rows = $apiHttpCode === 200 && is_array($response['data'] ?? null) ? $response['data'] : [];
        $users = array_map(static fn (array $row): User => User::fromArray($row), $rows);

        $this->view('users/index', [
            'title' => 'Usuarios',
            'users' => $users,
            'apiHttpCode' => $apiHttpCode,
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
            'startTime' => trim((string) $request->input('startTime', '')),
            'endTime' => trim((string) $request->input('endTime', '')),
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
            ->required('startTime', (string) $payload['startTime'], 'Hora de inicio')
            ->required('endTime', (string) $payload['endTime'], 'Hora de fin')
            ->email('email', (string) $payload['email'], 'Correo');

        if (!$isUpdate || ((string) $payload['password']) !== '') {
            $validator
                ->required('password', (string) $payload['password'], 'Contrasena')
                ->minLength('password', (string) $payload['password'], 8, 'Contrasena');
        }

        if ($isUpdate && (string) $payload['id'] === '') {
            return ['ID de usuario requerido para actualizar.'];
        }

        $startTime = (string) ($payload['startTime'] ?? '');
        $endTime = (string) ($payload['endTime'] ?? '');
        $timePattern = '/^([01]\\d|2[0-3]):[0-5]\\d:[0-5]\\d$/';

        if ($startTime !== '' && preg_match($timePattern, $startTime) !== 1) {
            $validatorErrors = $validator->errors();
            $validatorErrors['startTime'] = 'Hora de inicio no es valida.';
            return array_values($validatorErrors);
        }

        if ($endTime !== '' && preg_match($timePattern, $endTime) !== 1) {
            $validatorErrors = $validator->errors();
            $validatorErrors['endTime'] = 'Hora de fin no es valida.';
            return array_values($validatorErrors);
        }

        if ($startTime !== '' && $endTime !== '' && $endTime <= $startTime) {
            $validatorErrors = $validator->errors();
            $validatorErrors['workingHour'] = 'La hora de fin debe ser mayor a la hora de inicio.';
            return array_values($validatorErrors);
        }

        if ($validator->passes()) {
            return [];
        }

        return array_values($validator->errors());
    }
}
