<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\ServiceFactory;

/**
 * Role/permission management controller.
 */
final class PermissionController extends Controller
{
    /**
     * @param array<int, string> $allowedRoles
     */
    private function hasRole(array $allowedRoles): bool
    {
        $user = ServiceFactory::sessionManager()->getUser();
        $rolename = strtoupper(trim((string) ($user['rolename'] ?? '')));

        return in_array($rolename, $allowedRoles, true);
    }

    /**
     * API docs state '00' as the success code, but the live API returns '200'; accept both.
     */
    private function isSuccessCode(string $code): bool
    {
        return in_array($code, ['00', '200'], true);
    }

    public function addForm(Request $request): void
    {
        if (!$this->hasRole(['SUPER'])) {
            $this->view('permissions/add', [
                'title' => 'Agregar permisos',
                'roles' => [],
                'modules' => [],
                'apiHttpCode' => 403,
                'authUser' => ServiceFactory::sessionManager()->getUser(),
                'csrfToken' => get_csrf_token(),
                'flashMessages' => consume_flash(),
            ]);
            return;
        }

        $response = ServiceFactory::permissionService()->getScreenModules();
        $apiHttpCode = (int) ($response['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $code = (string) ($response['code'] ?? '');
        $roles = [];
        $modules = [];
        $apiMessage = (string) ($response['message'] ?? '');

        if ($apiHttpCode === 200 && $this->isSuccessCode($code) && is_array($response['data'] ?? null)) {
            $entry = is_array($response['data'][0] ?? null) ? $response['data'][0] : [];
            $roles = is_array($entry['roles'] ?? null) ? $entry['roles'] : [];
            $modules = is_array($entry['modules'] ?? null) ? $entry['modules'] : [];
        } elseif ($apiHttpCode === 200 && !$this->isSuccessCode($code)) {
            $apiHttpCode = 422;
        }

        $this->view('permissions/add', [
            'title' => 'Agregar permisos',
            'roles' => $roles,
            'modules' => $modules,
            'apiHttpCode' => $apiHttpCode,
            'apiMessage' => $apiMessage,
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }

    public function permissionsByScreen(Request $request): void
    {
        if (!$this->hasRole(['SUPER'])) {
            $this->json(['code' => '403', 'message' => 'No tiene permisos', 'data' => null], 403);
            return;
        }

        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido'], 403);
        }

        $keyScreen = trim((string) $request->input('key_screen', ''));
        if ($keyScreen === '') {
            $this->json(['code' => '422', 'message' => 'Debe seleccionar una pantalla.'], 422);
        }

        $response = ServiceFactory::permissionService()->getPermissionsByScreen($keyScreen);
        $this->json($response);
    }

    public function store(Request $request): void
    {
        if (!$this->hasRole(['SUPER'])) {
            $this->json(['code' => '403', 'message' => 'No tiene permisos', 'data' => null], 403);
            return;
        }

        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido'], 403);
        }

        $role = $request->input('role', null);
        $permissionKeys = $request->input('permission', []);

        if (!is_numeric($role)) {
            $this->json(['code' => '422', 'message' => 'Debe seleccionar un rol.'], 422);
        }

        if (!is_array($permissionKeys) || $permissionKeys === []) {
            $this->json(['code' => '422', 'message' => 'Debe seleccionar al menos un permiso.'], 422);
        }

        $permission = [];
        foreach ($permissionKeys as $key) {
            $key = trim((string) $key);
            if ($key !== '') {
                $permission[] = ['permision_key' => $key];
            }
        }

        if ($permission === []) {
            $this->json(['code' => '422', 'message' => 'Debe seleccionar al menos un permiso.'], 422);
        }

        $payload = [
            'role' => (int) $role,
            'permission' => $permission,
        ];

        $response = ServiceFactory::permissionService()->addRolePermission($payload);
        $this->json($response);
    }

    public function index(Request $request): void
    {
        if (!$this->hasRole(['SUPER'])) {
            $this->view('permissions/index', [
                'title' => 'Ver permisos',
                'rolePermissions' => [],
                'apiHttpCode' => 403,
                'authUser' => ServiceFactory::sessionManager()->getUser(),
                'csrfToken' => get_csrf_token(),
                'flashMessages' => consume_flash(),
            ]);
            return;
        }

        $response = ServiceFactory::permissionService()->getAllRolePermissions();
        $apiHttpCode = (int) ($response['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $code = (string) ($response['code'] ?? '');
        $rows = ($apiHttpCode === 200 && $this->isSuccessCode($code) && is_array($response['data'] ?? null))
            ? $response['data']
            : [];

        if ($apiHttpCode === 200 && !$this->isSuccessCode($code)) {
            $apiHttpCode = 422;
        }

        $this->view('permissions/index', [
            'title' => 'Ver permisos',
            'rolePermissions' => $rows,
            'apiHttpCode' => $apiHttpCode,
            'apiMessage' => (string) ($response['message'] ?? ''),
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }
}
