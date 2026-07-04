<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\ServiceFactory;

/**
 * Trace log viewer — ADMIN only.
 */
final class TraceController extends Controller
{
    public function index(Request $request): void
    {
        if (!$this->hasAdminRole()) {
            $this->view('traces/index', [
                'title'          => 'Trace Logs',
                'traces'         => [],
                'page'           => 1,
                'reg'            => 10,
                'totalRecords'   => 0,
                'apiHttpCode'    => 403,
                'authUser'       => ServiceFactory::sessionManager()->getUser(),
                'csrfToken'      => get_csrf_token(),
                'flashMessages'  => consume_flash(),
            ]);
            return;
        }

        $page = max(1, (int) $request->query('page', 1));
        $reg  = in_array((int) $request->query('reg', 10), [10, 25, 50, 100], true)
            ? (int) $request->query('reg', 10)
            : 10;

        $response    = ServiceFactory::traceService()->getTrace($page, $reg);
        $apiHttpCode = (int) ($response['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $rows         = $apiHttpCode === 200 && is_array($response['data'] ?? null) ? $response['data'] : [];
        $totalRecords = (int) ($response['totalRecords'] ?? (int) ($response['total'] ?? count($rows)));

        $this->view('traces/index', [
            'title'          => 'Trace Logs',
            'traces'         => $rows,
            'page'           => $page,
            'reg'            => $reg,
            'totalRecords'   => $totalRecords,
            'apiHttpCode'    => $apiHttpCode,
            'apiMessage'     => (string) ($response['message'] ?? ''),
            'authUser'       => ServiceFactory::sessionManager()->getUser(),
            'csrfToken'      => get_csrf_token(),
            'flashMessages'  => consume_flash(),
        ]);
    }

    /** AJAX – called from the frontend paginator */
    public function list(Request $request): void
    {
        if (!$this->hasAdminRole()) {
            $this->json(['code' => '403', 'message' => 'No tiene permisos', 'data' => null], 403);
            return;
        }

        $page = max(1, (int) $request->input('page', 1));
        $reg  = in_array((int) $request->input('reg', 10), [10, 25, 50, 100], true)
            ? (int) $request->input('reg', 10)
            : 10;

        $response = ServiceFactory::traceService()->getTrace($page, $reg);
        $this->json($response, (int) ($response['http_code'] ?? 200));
    }

    private function hasAdminRole(): bool
    {
        $user     = ServiceFactory::sessionManager()->getUser();
        $rolename = strtoupper(trim((string) ($user['rolename'] ?? '')));
        return $rolename === 'ADMIN';
    }
}
