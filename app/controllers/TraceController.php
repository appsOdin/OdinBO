<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\ServiceFactory;

/**
 * Trace log viewer — ADMIN and SUPER.
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

        $response = ServiceFactory::traceService()->getTrace($page, $reg);

        $normalized = $this->normalizeTraceResponse($response);

        $apiHttpCode = $normalized['http_code'];

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $rows = $apiHttpCode === 200 ? $normalized['rows'] : [];
        $totalRecords = $normalized['totalRecords'];

        $this->view('traces/index', [
            'title'          => 'Trace Logs',
            'traces'         => $rows,
            'page'           => $page,
            'reg'            => $reg,
            'totalRecords'   => $totalRecords,
            'apiHttpCode'    => $apiHttpCode,
            'apiMessage'     => $normalized['message'],
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

        $normalized = $this->normalizeTraceResponse($response);

        $this->json([
            'code' => $normalized['code'],
            'message' => $normalized['message'],
            'data' => $normalized['rows'],
            'totalRecords' => $normalized['totalRecords'],
        ], $normalized['http_code']);
    }

    /**
     * @param array<string, mixed> $response
     * @return array{code: string, message: string, http_code: int, rows: array<int, array<string, mixed>>, totalRecords: int}
     */
    private function normalizeTraceResponse(array $response): array
    {
        $httpCode = (int) ($response['http_code'] ?? 200);

        $codeRaw = $response['code'] ?? null;
        $code = is_scalar($codeRaw) && trim((string) $codeRaw) !== ''
            ? trim((string) $codeRaw)
            : ($httpCode >= 200 && $httpCode < 300 ? '200' : (string) $httpCode);

        $messageRaw = $response['message'] ?? ($response['data'] ?? null);
        $message = is_scalar($messageRaw) ? (string) $messageRaw : '';

        $data = $response['data'] ?? null;
        $rows = [];
        $totalRecords = 0;

        if (is_array($data)) {
            if ($this->isList($data)) {
                $rows = $this->filterRows($data);
                $totalRecords = count($rows);
            } else {
                $candidateRows = null;
                foreach (['data', 'rows', 'items', 'result'] as $key) {
                    if (isset($data[$key]) && is_array($data[$key])) {
                        $candidateRows = $data[$key];
                        break;
                    }
                }

                if (is_array($candidateRows)) {
                    $rows = $this->filterRows($candidateRows);
                }

                $totalRaw = $data['totalRecords'] ?? $data['total'] ?? $response['totalRecords'] ?? $response['total'] ?? count($rows);
                $totalRecords = is_numeric($totalRaw) ? (int) $totalRaw : count($rows);
            }
        }

        if ($totalRecords <= 0) {
            $totalRaw = $response['totalRecords'] ?? $response['total'] ?? count($rows);
            $totalRecords = is_numeric($totalRaw) ? (int) $totalRaw : count($rows);
        }

        return [
            'code' => $code,
            'message' => $message,
            'http_code' => $httpCode,
            'rows' => $rows,
            'totalRecords' => $totalRecords,
        ];
    }

    /**
     * @param array<int|string, mixed> $value
     */
    private function isList(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * @param array<int|string, mixed> $rows
     * @return array<int, array<string, mixed>>
     */
    private function filterRows(array $rows): array
    {
        return array_values(array_filter($rows, static function ($row): bool {
            return is_array($row);
        }));
    }

    private function hasAdminRole(): bool
    {
        $user     = ServiceFactory::sessionManager()->getUser();
        $rolename = strtoupper(trim((string) ($user['rolename'] ?? '')));
        return in_array($rolename, ['ADMIN', 'SUPER'], true);
    }
}
