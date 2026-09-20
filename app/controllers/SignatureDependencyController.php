<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\ServiceFactory;

/**
 * Signature dependency management controller.
 */
final class SignatureDependencyController extends Controller
{
    public function index(Request $request): void
    {
        $response = ServiceFactory::signatureDependencyService()->getAll();
        $apiHttpCode = (int) ($response['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $rows = $apiHttpCode === 200 && is_array($response['data'] ?? null) ? $response['data'] : [];

        $this->view('signature-dependencies/index', [
            'title' => 'Dependencia de Firmas',
            'dependencies' => $rows,
            'apiHttpCode' => $apiHttpCode,
            'apiMessage' => (string) ($response['message'] ?? ''),
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }

    public function list(Request $request): void
    {
        $response = ServiceFactory::signatureDependencyService()->getAll();
        $this->json($response, (int) ($response['http_code'] ?? 200));
    }

    public function users(Request $request): void
    {
        $response = ServiceFactory::signatureDependencyService()->getUsers();
        $this->json($response, (int) ($response['http_code'] ?? 200));
    }

    public function store(Request $request): void
    {
        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido', 'data' => null], 403);
            return;
        }

        $requestUserId = trim((string) $request->input('request_user_id', ''));
        $authorizeUserId = trim((string) $request->input('authorize_user_id', ''));

        if ($requestUserId === '') {
            $this->json(['code' => '422', 'message' => 'Debe seleccionar un solicitante.', 'data' => null], 422);
            return;
        }

        if ($authorizeUserId === '') {
            $this->json(['code' => '422', 'message' => 'Debe seleccionar un aprobador.', 'data' => null], 422);
            return;
        }

        $response = ServiceFactory::signatureDependencyService()->add([
            'request_user_id' => $requestUserId,
            'authorize_user_id' => $authorizeUserId,
        ]);

        $this->json($response, (int) ($response['http_code'] ?? 200));
    }

    public function update(Request $request): void
    {
        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido', 'data' => null], 403);
            return;
        }

        $id = (int) $request->input('id', 0);
        $requestUserId = trim((string) $request->input('request_user_id', ''));
        $authorizeUserId = trim((string) $request->input('authorize_user_id', ''));

        if ($id <= 0) {
            $this->json(['code' => '422', 'message' => 'El registro seleccionado es invalido.', 'data' => null], 422);
            return;
        }

        if ($requestUserId === '') {
            $this->json(['code' => '422', 'message' => 'Debe seleccionar un solicitante.', 'data' => null], 422);
            return;
        }

        if ($authorizeUserId === '') {
            $this->json(['code' => '422', 'message' => 'Debe seleccionar un aprobador.', 'data' => null], 422);
            return;
        }

        $response = ServiceFactory::signatureDependencyService()->update([
            'id' => $id,
            'request_user_id' => $requestUserId,
            'authorize_user_id' => $authorizeUserId,
        ]);

        $this->json($response, (int) ($response['http_code'] ?? 200));
    }
}
