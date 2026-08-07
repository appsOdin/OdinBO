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
            if (is_ajax_request()) {
                json_response([
                    'code' => '401',
                    'message' => 'La sesion ha vencido. Inicia sesion nuevamente.',
                    'data' => null,
                ], 401);
            }

            flash('danger', 'Tu sesion ha expirado, inicia sesion nuevamente.');
            redirect('/login');
        }

        $user = $session->getUser();
        $rolename = strtoupper(trim((string) ($user['rolename'] ?? '')));

        if ($rolename === 'SUPER') {
            return;
        }

        if ($this->isAllowedForRole($rolename, $request->method(), $request->path())) {
            return;
        }

        if (is_ajax_request()) {
            json_response([
                'code' => '403',
                'message' => 'No tiene permisos para acceder a este recurso.',
                'data' => null,
            ], 403);
        }

        flash('danger', 'No tiene permisos para acceder a este recurso.');
        redirect($this->defaultPathForRole($rolename));
    }

    private function isAllowedForRole(string $role, string $method, string $path): bool
    {
        $routeKey = strtoupper($method) . ' ' . $path;
        if ($role === 'ADMIN') {
            $forbidden = [
                'GET /users',
                'GET /users/list',
                'POST /users/store',
                'POST /users/update',
            ];

            return !in_array($routeKey, $forbidden, true);
        }
        /*
        *Se define un conjunto de rutas permitidas para el rol USER, ya que este rol tiene acceso limitado a ciertas funcionalidades del sistema. 
        * En route se especifican las rutas.
         */
        if ($role === 'USER') {
            $allowed = [
                'POST /logout',
                'GET /dashboard',
                'GET /rrhh/solicitud-vacaciones',
                'GET /rrhh/solicitud-vacaciones/crear',
                'GET /rrhh/solicitud-vacaciones/detalle',
                'POST /rrhh/solicitud-vacaciones/store',
                'GET /rrhh/solicitudes-para-firmar',
                'GET /rrhh/vacaciones/descargar',
                'POST /rrhh/solicitudes-vacaciones/files',
                'POST /rrhh/solicitud-vacaciones/save-signature',
                'POST /rrhh/solicitud-vacaciones/reject',
                'POST /rrhh/solicitud-vacaciones/adjust',
                'POST /rrhh/solicitud-vacaciones/upload-comprobante',
                'GET /articles',
                'POST /articles/list',
                'POST /articles/detail',
                'GET /users/change-password',
                'POST /users/update-password',
            ];

            return in_array($routeKey, $allowed, true);
        }
        if ($role === 'GUEST') {
            $allowed = [
                'POST /logout',
                'GET /dashboard',
                'GET /rrhh/solicitud-vacaciones',
                'GET /rrhh/solicitud-vacaciones/crear',
                'GET /rrhh/solicitud-vacaciones/detalle',
                'POST /rrhh/solicitud-vacaciones/store',
                'GET /rrhh/vacaciones/descargar',
                'POST /rrhh/solicitud-vacaciones/save-signature',
                'POST /rrhh/solicitud-vacaciones/reject',
                'POST /rrhh/solicitud-vacaciones/adjust',
                'POST /rrhh/solicitud-vacaciones/upload-comprobante',
                'GET /users/change-password',
                'POST /users/update-password',
            ];

            return in_array($routeKey, $allowed, true);
        }

        return false;
    }

    private function defaultPathForRole(string $role): string
    {
        return $role === 'USER' ? '/rrhh/solicitud-vacaciones' : '/dashboard';
    }
}
