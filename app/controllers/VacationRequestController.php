<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Core\Request;
use App\Services\ServiceFactory;

/**
 * Vacation requests module controller.
 */
final class VacationRequestController extends Controller
{
    public function my(Request $request): void
    {
        $response = ServiceFactory::vacationRequestService()->getMy();
        $apiHttpCode = (int) ($response['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $rows = $apiHttpCode === 200 && is_array($response['data'] ?? null) ? $response['data'] : [];

        $this->view('vacations/my', [
            'title' => 'Mis solicitudes de vacaciones',
            'requests' => $rows,
            'apiHttpCode' => $apiHttpCode,
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }

    public function all(Request $request): void
    {
        if (!$this->hasRole(['ADMIN', 'SUPER'])) {
            $this->view('vacations/all', [
                'title' => 'Solicitudes de vacaciones',
                'requests' => [],
                'users' => [],
                'apiHttpCode' => 403,
                'authUser' => ServiceFactory::sessionManager()->getUser(),
                'csrfToken' => get_csrf_token(),
                'flashMessages' => consume_flash(),
            ]);
            return;
        }

        $vacationResponse = ServiceFactory::vacationRequestService()->getAll();
        $apiHttpCode = (int) ($vacationResponse['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($vacationResponse['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $requests = $apiHttpCode === 200 && is_array($vacationResponse['data'] ?? null) ? $vacationResponse['data'] : [];

        $signersResponse = ServiceFactory::vacationRequestService()->getAllSigners();
        $usersRows = is_array($signersResponse['data'] ?? null) ? $signersResponse['data'] : [];
        $users = array_values(array_filter($usersRows, static function ($user): bool {
            return is_array($user);
        }));

        $this->view('vacations/all', [
            'title' => 'Solicitudes de vacaciones',
            'requests' => $requests,
            'users' => $users,
            'apiHttpCode' => $apiHttpCode,
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('vacations/create', [
            'title' => 'Crear solicitud de vacaciones',
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }

    public function store(Request $request): void
    {
        $csrfToken = (string) $request->input('_csrf_token', '');
        if (!validate_csrf_token($csrfToken)) {
            flash('danger', 'Token CSRF invalido.');
            $this->redirect('/rrhh/solicitud-vacaciones/crear');
            return;
        }

        $startDateRaw = sanitize_text((string) $request->input('start_date', ''));
        $endDateRaw = sanitize_text((string) $request->input('end_date', ''));
        $description = sanitize_text((string) $request->input('description', ''));
        $quantityInput = (int) $request->input('quantity', 0);
        $requestTypeRaw = $request->input('request_type', null);

        if (!in_array((string) $requestTypeRaw, ['0', '1'], true)) {
            flash('danger', 'Tipo de solicitud invalido. Seleccione Vacaciones o Permiso.');
            $this->redirect('/rrhh/solicitud-vacaciones/crear');
            return;
        }
        $requestType = (int) $requestTypeRaw;

        $startDate = \DateTimeImmutable::createFromFormat('Y-m-d', $startDateRaw);
        $endDate = \DateTimeImmutable::createFromFormat('Y-m-d', $endDateRaw);

        if (!$startDate || $startDate->format('Y-m-d') !== $startDateRaw) {
            flash('danger', 'Fecha de inicio invalida.');
            $this->redirect('/rrhh/solicitud-vacaciones/crear');
            return;
        }

        if (!$endDate || $endDate->format('Y-m-d') !== $endDateRaw) {
            flash('danger', 'Fecha de fin invalida.');
            $this->redirect('/rrhh/solicitud-vacaciones/crear');
            return;
        }

        $today = new \DateTimeImmutable(date('Y-m-d'));
        if ($startDate < $today) {
            flash('danger', 'La fecha de inicio no puede ser menor a la fecha actual.');
            $this->redirect('/rrhh/solicitud-vacaciones/crear');
            return;
        }

        if ($requestType === 1) {
            // Permiso: start and end must be the same date; quantity = hours
            if ($endDate->format('Y-m-d') !== $startDate->format('Y-m-d')) {
                flash('danger', 'Para permisos, la fecha de inicio y fin deben ser el mismo dia.');
                $this->redirect('/rrhh/solicitud-vacaciones/crear');
                return;
            }
            if ($quantityInput < 1 || $quantityInput > 999) {
                flash('danger', 'La cantidad de horas debe estar entre 1 y 999.');
                $this->redirect('/rrhh/solicitud-vacaciones/crear');
                return;
            }
            $finalQuantity = $quantityInput;
        } else {
            // Vacaciones: end >= start, quantity = inclusive days
            if ($endDate < $startDate) {
                flash('danger', 'La fecha de fin no puede ser anterior a la fecha de inicio.');
                $this->redirect('/rrhh/solicitud-vacaciones/crear');
                return;
            }
            $finalQuantity = 0;
            $cur = $startDate;
            while ($cur <= $endDate) {
                $dayOfWeek = (int) $cur->format('w');
                $mmdd = $cur->format('m-d');
                if ($dayOfWeek !== 0 && !in_array($mmdd, HOLIDAYS, true)) { // excluye domingos y feriados
                    $finalQuantity++;
                }
                $cur = $cur->modify('+1 day');
            }
            if ($finalQuantity < 1 || $finalQuantity > 255) {
                flash('danger', 'La cantidad de dias debe estar entre 1 y 255 (sin contar domingos ni feriados).');
                $this->redirect('/rrhh/solicitud-vacaciones/crear');
                return;
            }
        }

        if ($description === '' || mb_strlen($description, 'UTF-8') > 100) {
            flash('danger', 'La descripcion es obligatoria y no puede superar 100 caracteres.');
            $this->redirect('/rrhh/solicitud-vacaciones/crear');
            return;
        }

        $uploadedFiles = $this->resolveUploadedFiles();
        if ($uploadedFiles === null) {
            flash('danger', 'Uno o mas archivos adjuntos no son validos. Solo se permiten PDF, JPG o PNG.');
            $this->redirect('/rrhh/solicitud-vacaciones/crear');
            return;
        }

        Logger::info('VacationRequest store files parsed', [
            'request_type' => $requestType,
            'parsed_files_count' => count($uploadedFiles),
            'raw_files_keys' => array_keys($_FILES),
        ]);

        $response = ServiceFactory::vacationRequestService()->create(
            $startDate->format('Y-m-d') . 'T00:00:00',
            $endDate->format('Y-m-d') . 'T00:00:00',
            $finalQuantity,
            $description,
            $requestType,
            $uploadedFiles
        );
        $httpCode = (int) ($response['http_code'] ?? 0);
        $code = trim((string) ($response['code'] ?? ''));

        if ($httpCode === 401 || $httpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        if ($httpCode === 200 && $code === '200') {
            flash('success', 'Solicitud creada exitosamente.');
            $this->redirect('/rrhh/solicitud-vacaciones');
            return;
        }

        flash('danger', (string) ($response['message'] ?? 'No fue posible crear la solicitud.'));
        $this->redirect('/rrhh/solicitud-vacaciones/crear');
    }

    public function signers(Request $request): void
    {
        if (!$this->hasRole(['ADMIN', 'SUPER', 'USER'])) {
            $this->json(['code' => '403', 'message' => 'No tiene permisos', 'data' => null], 403);
            return;
        }

        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido', 'data' => null], 403);
            return;
        }

        $requestId = (int) $request->input('requestId', 0);
        if ($requestId <= 0) {
            $this->json(['code' => '422', 'message' => 'ID de solicitud invalido', 'data' => null], 422);
            return;
        }

        $response = ServiceFactory::vacationRequestService()->getSigners($requestId);
        $this->json($response, (int) ($response['http_code'] ?? 200));
    }

    public function files(Request $request): void
    {
        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido', 'data' => null], 403);
            return;
        }

        $requestId = (int) $request->input('requestId', 0);
        if ($requestId <= 0) {
            $this->json(['code' => '422', 'message' => 'ID de solicitud invalido', 'data' => null], 422);
            return;
        }

        $response = ServiceFactory::vacationRequestService()->getFiles($requestId);
        $this->json($response, (int) ($response['http_code'] ?? 200));
    }

    public function addSigners(Request $request): void
    {
        if (!$this->hasRole(['ADMIN', 'SUPER'])) {
            $this->json(['code' => '403', 'message' => 'No tiene permisos', 'data' => null], 403);
            return;
        }

        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido', 'data' => null], 403);
            return;
        }

        $requestId = (int) $request->input('requestId', 0);
        if ($requestId <= 0) {
            $this->json(['code' => '422', 'message' => 'ID de solicitud invalido', 'data' => null], 422);
            return;
        }

        $rawSignerIds = $request->input('signers', []);
        $signerIds = is_array($rawSignerIds) ? array_values(array_filter(array_map(static function ($value): string {
            return sanitize_text((string) $value);
        }, $rawSignerIds))) : [];

        if ($signerIds === []) {
            $this->json(['code' => '422', 'message' => 'Debe seleccionar al menos un firmante', 'data' => null], 422);
            return;
        }

        foreach ($signerIds as $signerId) {
            if (!preg_match('/^[A-Za-z0-9_-]{1,50}$/', $signerId)) {
                $this->json(['code' => '422', 'message' => 'Identificador de firmante invalido', 'data' => null], 422);
                return;
            }
        }

        if (!isset($_FILES['pdfFile']) || !is_array($_FILES['pdfFile'])) {
            $this->json(['code' => '422', 'message' => 'Debe adjuntar un archivo PDF', 'data' => null], 422);
            return;
        }

        $pdfFile = $_FILES['pdfFile'];
        $uploadError = (int) ($pdfFile['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $this->json(['code' => '422', 'message' => 'No fue posible procesar el archivo PDF', 'data' => null], 422);
            return;
        }

        $tmpName = (string) ($pdfFile['tmp_name'] ?? '');
        $originalName = (string) ($pdfFile['name'] ?? '');
        $size = (int) ($pdfFile['size'] ?? 0);

        if (!is_uploaded_file($tmpName)) {
            $this->json(['code' => '422', 'message' => 'Archivo PDF invalido', 'data' => null], 422);
            return;
        }

        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            $this->json(['code' => '422', 'message' => 'El archivo PDF debe pesar entre 1 byte y 5 MB', 'data' => null], 422);
            return;
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            $this->json(['code' => '422', 'message' => 'Solo se permite formato PDF', 'data' => null], 422);
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        if (!is_string($mime) || !in_array(strtolower($mime), ['application/pdf', 'application/x-pdf'], true)) {
            $this->json(['code' => '422', 'message' => 'El archivo no corresponde a un PDF valido', 'data' => null], 422);
            return;
        }

        $response = ServiceFactory::vacationRequestService()->addSigners($requestId, $signerIds, [
            'name' => basename($originalName),
            'tmp_name' => $tmpName,
            'type' => $mime,
        ]);

        $this->json($response, (int) ($response['http_code'] ?? 200));
    }

    public function saveSignature(Request $request): void
    {
        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido', 'data' => null], 403);
            return;
        }

        $requestId = (int) $request->input('requestId', 0);
        $signature = (string) $request->input('signature', '');

        if ($requestId <= 0) {
            $this->json(['code' => '422', 'message' => 'ID de solicitud invalido', 'data' => null], 422);
            return;
        }

        if (!preg_match('/^data:image\/png;base64,/', $signature)) {
            $this->json(['code' => '422', 'message' => 'Formato de firma invalido', 'data' => null], 422);
            return;
        }

        $base64 = substr($signature, strlen('data:image/png;base64,'));
        $binary = base64_decode($base64, true);

        if ($binary === false || strlen($binary) < 100) {
            $this->json(['code' => '422', 'message' => 'Firma vacia o invalida', 'data' => null], 422);
            return;
        }

        if (strlen($binary) > 2 * 1024 * 1024) {
            $this->json(['code' => '422', 'message' => 'La firma supera el tamano permitido (2 MB)', 'data' => null], 422);
            return;
        }

        $response = ServiceFactory::vacationRequestService()->signRequest($requestId, $signature);
        $httpCode = (int) ($response['http_code'] ?? 0);

        if ($httpCode === 401) {
            ServiceFactory::authService()->logout();
            $this->json(['code' => '401', 'message' => 'Sesion expirada', 'data' => null], 401);
            return;
        }

        $this->json($response, max(200, $httpCode));
    }

    public function toSign(Request $request): void
    {
        if (!$this->hasRole(['ADMIN', 'SUPER', 'USER'])) {
            $this->view('vacations/to-sign', [
                'title' => 'Solicitudes para firmar',
                'requests' => [],
                'apiHttpCode' => 403,
                'authUser' => ServiceFactory::sessionManager()->getUser(),
                'csrfToken' => get_csrf_token(),
                'flashMessages' => consume_flash(),
            ]);
            return;
        }

        $response = ServiceFactory::vacationRequestService()->getRequestsToSign();
        $apiHttpCode = (int) ($response['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $rows = $apiHttpCode === 200 && is_array($response['data'] ?? null) ? $response['data'] : [];
        $rows = array_values(array_filter($rows, static function (array $row): bool {
            $stateKey = strtoupper((string) ($row['stateKey'] ?? ''));
            $stateName = strtoupper((string) ($row['stateName'] ?? ''));

            return $stateKey !== 'REJECTED' && $stateName !== 'RECHAZADO';
        }));

        $this->view('vacations/to-sign', [
            'title' => 'Solicitudes para firmar',
            'requests' => $rows,
            'apiHttpCode' => $apiHttpCode,
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }

    public function calendar(Request $request): void
    {
        if (!$this->hasRole(['ADMIN', 'SUPER'])) {
            $this->view('vacations/calendar', [
                'title' => 'Calendario de vacaciones',
                'entries' => [],
                'apiHttpCode' => 403,
                'authUser' => ServiceFactory::sessionManager()->getUser(),
                'csrfToken' => get_csrf_token(),
                'flashMessages' => consume_flash(),
            ]);
            return;
        }

        $response = ServiceFactory::vacationRequestService()->getRequestVacation('');
        $apiHttpCode = (int) ($response['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $rows = $apiHttpCode === 200 && is_array($response['data'] ?? null) ? $response['data'] : [];
        $entries = array_values(array_filter($rows, static function ($row): bool {
            return is_array($row);
        }));

        $this->view('vacations/calendar', [
            'title' => 'Calendario de vacaciones',
            'entries' => $entries,
            'apiHttpCode' => $apiHttpCode,
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }

    public function vacationReport(Request $request): void
    {
        if (!$this->hasRole(['ADMIN', 'SUPER'])) {
            $this->view('reports/vacations', [
                'title' => 'Reporte de vacaciones',
                'rows' => [],
                'signers' => [],
                'apiHttpCode' => 403,
                'authUser' => ServiceFactory::sessionManager()->getUser(),
                'csrfToken' => get_csrf_token(),
                'flashMessages' => consume_flash(),
            ]);
            return;
        }

        $signersResponse = ServiceFactory::vacationRequestService()->getAllSigners();
        $signersHttpCode = (int) ($signersResponse['http_code'] ?? 200);
        if ($signersHttpCode === 401 || $signersHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($signersResponse['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $signersRows = is_array($signersResponse['data'] ?? null) ? $signersResponse['data'] : [];
        $signers = array_values(array_filter($signersRows, static function ($row): bool {
            return is_array($row);
        }));

        $this->view('reports/vacations', [
            'title' => 'Reporte de vacaciones',
            'rows' => [],
            'signers' => $signers,
            'apiHttpCode' => 200,
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }

    public function vacationReportList(Request $request): void
    {
        if (!$this->hasRole(['ADMIN', 'SUPER'])) {
            $this->json(['code' => '403', 'message' => 'No tiene permisos', 'data' => null], 403);
            return;
        }

        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido', 'data' => null], 403);
            return;
        }

        $identification = sanitize_text((string) $request->input('identification', ''));
        if ($identification !== '' && !preg_match('/^[A-Za-z0-9_-]{1,50}$/', $identification)) {
            $this->json(['code' => '422', 'message' => 'Identificacion invalida', 'data' => null], 422);
            return;
        }

        $response = ServiceFactory::vacationRequestService()->getRequestVacation($identification);
        $httpCode = (int) ($response['http_code'] ?? 200);

        if ($httpCode === 401) {
            ServiceFactory::authService()->logout();
            $this->json(['code' => '401', 'message' => 'Sesion expirada', 'data' => null], 401);
            return;
        }

        $this->json($response, $httpCode >= 100 ? $httpCode : 200);
    }

    public function detail(Request $request): void
    {
        $requestId = (int) $request->query('id', 0);
        if ($requestId <= 0) {
            flash('danger', 'ID de solicitud invalido.');
            $this->redirect('/rrhh/solicitud-vacaciones');
            return;
        }

        $response = ServiceFactory::vacationRequestService()->getDetail($requestId);
        $apiHttpCode = (int) ($response['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        if ($apiHttpCode !== 200) {
            flash('danger', (string) ($response['message'] ?? 'No fue posible cargar el detalle.'));
            $this->redirect('/rrhh/solicitud-vacaciones');
            return;
        }

        $detail = is_array($response['data'] ?? null) ? $response['data'] : [];

        $filesResponse = ServiceFactory::vacationRequestService()->getFiles($requestId);
        $filesHttpCode = (int) ($filesResponse['http_code'] ?? 0);

        if ($filesHttpCode === 401 || $filesHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($filesResponse['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        $filesRows = is_array($filesResponse['data'] ?? null) ? $filesResponse['data'] : [];
        if ($filesRows !== []) {
            $detail['files'] = array_values(array_filter($filesRows, static function ($row): bool {
                return is_array($row);
            }));
        } elseif (!is_array($detail['files'] ?? null)) {
            $detail['files'] = [];
        }

        $this->view('vacations/detail', [
            'title' => 'Detalle de solicitud de vacaciones',
            'detail' => $detail,
            'apiHttpCode' => $apiHttpCode,
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }

    public function downloadFile(Request $request): void
    {
        $fileId = (int) $request->query('fileId', 0);
        $viewInline = (string) $request->query('view', '0') === '1';
        if ($fileId <= 0) {
            http_response_code(422);
            echo 'ID de archivo invalido';
            exit;
        }

        $result = ServiceFactory::vacationRequestService()->downloadFile($fileId);
        $httpCode = (int) ($result['http_code'] ?? 500);

        if ($httpCode === 401 || $httpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($result['message'] ?? 'Sesion expirada.'));
            $this->redirect('/login');
            return;
        }

        if ($httpCode !== 200) {
            http_response_code($httpCode >= 400 ? $httpCode : 500);
            echo 'No fue posible descargar el archivo';
            exit;
        }

        $contentType = strtolower(trim((string) ($result['content_type'] ?? '')));
        $allowedTypes = ['application/pdf', 'application/octet-stream', 'image/jpeg', 'image/png'];
        if (!in_array($contentType, $allowedTypes, true)) {
            $contentType = 'application/octet-stream';
        }

        // Build a safe filename from content-disposition or fallback
        $rawDisposition = (string) ($result['content_disposition'] ?? '');
        $filename = 'archivo.pdf';
        if (preg_match('/filename=["\']?([^"\'\s;]+)["\']?/i', $rawDisposition, $matches)) {
            $filename = basename(preg_replace('/[^\w\s.\-]/', '', $matches[1]) ?? 'archivo.pdf');
            if ($filename === '') {
                $filename = 'archivo.pdf';
            }
        }

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: ' . ($viewInline ? 'inline' : 'attachment') . '; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: private');
        header('X-Content-Type-Options: nosniff');

        echo $result['body'];
        exit;
    }

    public function reject(Request $request): void
    {
        $state = strtoupper(trim((string) $request->input('state', 'REJECTED')));
        $rejectReason = trim((string) $request->input('rejectReason', ''));
        $sing = trim((string) $request->input('sing', ''));

        $allowedStates = ['REJECTED', 'ANNULLED', 'ANNULLED_APPROVED'];
        if (!in_array($state, $allowedStates, true)) {
            $this->json(['code' => '422', 'message' => 'Estado invalido', 'data' => null], 422);
            return;
        }

        $rolesByState = [
            'REJECTED' => ['ADMIN', 'SUPER'],
            'ANNULLED' => ['ADMIN', 'SUPER', 'USER'],
            'ANNULLED_APPROVED' => ['ADMIN', 'SUPER'],
        ];

        if (!$this->hasRole($rolesByState[$state])) {
            $this->json(['code' => '403', 'message' => 'No tiene permisos para ejecutar esta accion', 'data' => null], 403);
            return;
        }

        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido', 'data' => null], 403);
            return;
        }

        $requestId = (int) $request->input('requestId', 0);
        if ($requestId <= 0) {
            $this->json(['code' => '422', 'message' => 'ID de solicitud invalido', 'data' => null], 422);
            return;
        }

        if (in_array($state, ['REJECTED', 'ANNULLED'], true)) {
            if ($rejectReason === '') {
                $message = $state === 'ANNULLED'
                    ? 'El motivo de la anulacion es obligatorio'
                    : 'El motivo del rechazo es obligatorio';
                $this->json(['code' => '422', 'message' => $message, 'data' => null], 422);
                return;
            }

            if (mb_strlen($rejectReason, 'UTF-8') > 200) {
                $this->json(['code' => '422', 'message' => 'El motivo no puede superar 200 caracteres', 'data' => null], 422);
                return;
            }
        } else {
            $rejectReason = '';
        }

        if ($state === 'ANNULLED') {
            if (!preg_match('/^data:image\/png;base64,/', $sing)) {
                $this->json(['code' => '422', 'message' => 'Formato de firma invalido', 'data' => null], 422);
                return;
            }

            $base64 = substr($sing, strlen('data:image/png;base64,'));
            $binary = base64_decode($base64, true);

            if ($binary === false || strlen($binary) < 100) {
                $this->json(['code' => '422', 'message' => 'Firma vacia o invalida', 'data' => null], 422);
                return;
            }

            if (strlen($binary) > 2 * 1024 * 1024) {
                $this->json(['code' => '422', 'message' => 'La firma supera el tamano permitido (2 MB)', 'data' => null], 422);
                return;
            }
        } else {
            $sing = '';
        }

        $detailResponse = ServiceFactory::vacationRequestService()->getDetail($requestId);
        $detailHttpCode = (int) ($detailResponse['http_code'] ?? 0);
        if ($detailHttpCode === 401) {
            ServiceFactory::authService()->logout();
            $this->json(['code' => '401', 'message' => 'Sesion expirada', 'data' => null], 401);
            return;
        }
        if ($detailHttpCode === 406) {
            $this->json($detailResponse, 406);
            return;
        }
        if ($detailHttpCode !== 200 || !is_array($detailResponse['data'] ?? null)) {
            $this->json(['code' => '422', 'message' => 'No fue posible verificar la solicitud', 'data' => null], 422);
            return;
        }

        $detail = $detailResponse['data'];
        $startDateRaw = (string) ($detail['startDate'] ?? '');
        $startDateTs = $startDateRaw !== '' ? strtotime($startDateRaw) : false;

        if (in_array($state, ['REJECTED', 'ANNULLED'], true) && $startDateTs !== false && $startDateTs < strtotime(date('Y-m-d'))) {
            $message = $state === 'ANNULLED'
                ? 'No se puede anular una solicitud cuya fecha de inicio ya paso'
                : 'No se puede rechazar una solicitud cuya fecha de inicio ya paso';
            $this->json(['code' => '422', 'message' => $message, 'data' => null], 422);
            return;
        }

        $stateKey = strtoupper((string) ($detail['stateKey'] ?? ''));
        if ($state === 'REJECTED' && $stateKey === 'REJECTED') {
            $this->json(['code' => '422', 'message' => 'La solicitud ya fue rechazada', 'data' => null], 422);
            return;
        }

        if ($state === 'ANNULLED' && $stateKey === 'ANNULLED') {
            $this->json(['code' => '422', 'message' => 'La solicitud ya fue anulada', 'data' => null], 422);
            return;
        }

        if ($state === 'ANNULLED_APPROVED' && $stateKey !== 'ANNULLED') {
            $this->json(['code' => '422', 'message' => 'Solo se pueden aprobar anulaciones en estado ANULADA', 'data' => null], 422);
            return;
        }

        $response = ServiceFactory::vacationRequestService()->reject(
            $requestId,
            $rejectReason !== '' ? $rejectReason : null,
            $state,
            $sing !== '' ? $sing : null
        );
        $httpCode = (int) ($response['http_code'] ?? 0);

        if ($httpCode === 401) {
            ServiceFactory::authService()->logout();
            $this->json(['code' => '401', 'message' => 'Sesion expirada', 'data' => null], 401);
            return;
        }

        $apiCodeRaw = $response['code'] ?? null;
        $apiCode = is_scalar($apiCodeRaw) ? trim((string) $apiCodeRaw) : '';
        if ($httpCode >= 200 && $httpCode < 300 && $apiCode === '200') {
            $defaultMessage = match ($state) {
                'ANNULLED' => 'Solicitud anulada exitosamente',
                'ANNULLED_APPROVED' => 'Anulacion aprobada exitosamente',
                default => 'Solicitud rechazada exitosamente',
            };
            $message = (string) ($response['data'] ?? ($response['message'] ?? $defaultMessage));
            $this->json(['code' => '200', 'success' => true, 'message' => $message]);
            return;
        }

        $errorMsg = (string) ($response['errorMessage'] ?? ($response['message'] ?? 'No fue posible rechazar la solicitud'));
        $this->json(['code' => (string) $httpCode, 'success' => false, 'message' => $errorMsg], $httpCode >= 400 ? $httpCode : 400);
    }

    public function adjust(Request $request): void
    {
        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido', 'data' => null], 403);
            return;
        }

        $requestId = (int) $request->input('requestId', 0);
        $state = strtoupper(trim((string) $request->input('state', '')));
        $reason = trim((string) $request->input('reason', ''));
        $requestCantRaw = $request->input('requestCant', null);
        $sing = trim((string) $request->input('sing', ''));

        if ($requestId <= 0) {
            $this->json(['code' => '422', 'message' => 'ID de solicitud invalido', 'data' => null], 422);
            return;
        }

        if (!in_array($state, ['ADJUSTED', 'ADJUSTMENT_ACCEPTED'], true)) {
            $this->json(['code' => '422', 'message' => 'Estado invalido para ajuste', 'data' => null], 422);
            return;
        }

        $detailResponse = ServiceFactory::vacationRequestService()->getDetail($requestId);
        $detailHttpCode = (int) ($detailResponse['http_code'] ?? 0);

        if ($detailHttpCode === 401) {
            ServiceFactory::authService()->logout();
            $this->json(['code' => '401', 'message' => 'Sesion expirada', 'data' => null], 401);
            return;
        }

        if ($detailHttpCode === 406) {
            $this->json($detailResponse, 406);
            return;
        }

        if ($detailHttpCode !== 200 || !is_array($detailResponse['data'] ?? null)) {
            $this->json(['code' => '422', 'message' => 'No fue posible verificar la solicitud', 'data' => null], 422);
            return;
        }

        $detail = $detailResponse['data'];
        $requestType = (int) ($detail['requestType'] ?? -1);
        $stateKey = strtoupper((string) ($detail['stateKey'] ?? ''));
        $currentQuantity = (int) ($detail['quantity'] ?? 0);

        if ($requestType !== 1) {
            $this->json(['code' => '422', 'message' => 'Solo se pueden ajustar solicitudes de tipo Permiso', 'data' => null], 422);
            return;
        }

        $requestCant = null;
        if ($state === 'ADJUSTED') {
            if ($stateKey !== 'SIGN') {
                $this->json(['code' => '422', 'message' => 'Solo se pueden ajustar solicitudes en estado FIRMADA', 'data' => null], 422);
                return;
            }

            if ($reason === '') {
                $this->json(['code' => '422', 'message' => 'La razon del ajuste es obligatoria', 'data' => null], 422);
                return;
            }

            if (mb_strlen($reason, 'UTF-8') > 200) {
                $this->json(['code' => '422', 'message' => 'La razon no puede superar 200 caracteres', 'data' => null], 422);
                return;
            }

            $requestCant = is_numeric($requestCantRaw) ? (int) $requestCantRaw : 0;
            if ($requestCant < 1) {
                $this->json(['code' => '422', 'message' => 'La cantidad ajustada debe ser mayor o igual a 1', 'data' => null], 422);
                return;
            }

            if ($requestCant > 255) {
                $this->json(['code' => '422', 'message' => 'La cantidad ajustada no puede superar 255', 'data' => null], 422);
                return;
            }

            $sing = '';
        }

        if ($state === 'ADJUSTMENT_ACCEPTED') {
            if ($stateKey !== 'ADJUSTED') {
                $this->json(['code' => '422', 'message' => 'Solo se puede aprobar ajuste cuando la solicitud esta AJUSTADA', 'data' => null], 422);
                return;
            }

            if (!preg_match('/^data:image\/png;base64,/', $sing)) {
                $this->json(['code' => '422', 'message' => 'Formato de firma invalido', 'data' => null], 422);
                return;
            }

            $base64 = substr($sing, strlen('data:image/png;base64,'));
            $binary = base64_decode($base64, true);

            if ($binary === false || strlen($binary) < 100) {
                $this->json(['code' => '422', 'message' => 'Firma vacia o invalida', 'data' => null], 422);
                return;
            }

            if (strlen($binary) > 2 * 1024 * 1024) {
                $this->json(['code' => '422', 'message' => 'La firma supera el tamano permitido (2 MB)', 'data' => null], 422);
                return;
            }

            // The API binds requestCant as Byte, so it must always be a numeric value in 1..255.
            if ($currentQuantity >= 1 && $currentQuantity <= 255) {
                $requestCant = $currentQuantity;
            } else {
                $requestCant = 1;
            }

            $reason = '';
        }

        if (!is_int($requestCant) || $requestCant < 1 || $requestCant > 255) {
            $this->json(['code' => '422', 'message' => 'La cantidad del ajuste debe estar entre 1 y 255', 'data' => null], 422);
            return;
        }

        $apiReason = $state === 'ADJUSTMENT_ACCEPTED'
            ? ''
            : ($reason !== '' ? $reason : null);

        $response = ServiceFactory::vacationRequestService()->adjustVacationRequest(
            $requestId,
            $apiReason,
            $requestCant,
            $state,
            $sing !== '' ? $sing : null
        );

        $httpCode = (int) ($response['http_code'] ?? 0);

        if ($httpCode === 401) {
            ServiceFactory::authService()->logout();
            $this->json(['code' => '401', 'message' => 'Sesion expirada', 'data' => null], 401);
            return;
        }

        if ($httpCode === 406) {
            $this->json($response, 406);
            return;
        }

        $apiCodeRaw = $response['code'] ?? null;
        $apiCode = is_scalar($apiCodeRaw) ? trim((string) $apiCodeRaw) : '';
        if ($httpCode >= 200 && $httpCode < 300 && $apiCode === '200') {
            $defaultMessage = $state === 'ADJUSTMENT_ACCEPTED'
                ? 'Ajuste aprobado exitosamente'
                : 'Solicitud ajustada exitosamente';
            $message = (string) ($response['message'] ?? $defaultMessage);
            $this->json(['code' => '200', 'success' => true, 'message' => $message]);
            return;
        }

        $errorMsg = (string) ($response['data'] ?? ($response['message'] ?? 'No fue posible procesar el ajuste'));
        $this->json(['code' => (string) $httpCode, 'success' => false, 'message' => $errorMsg], $httpCode >= 400 ? $httpCode : 400);
    }

    public function uploadFileVacationRequest(Request $request): void
    {
        if (!validate_csrf_token((string) $request->input('_csrf_token', ''))) {
            $this->json(['code' => '403', 'message' => 'Token CSRF invalido', 'data' => null], 403);
            return;
        }

        $requestId = (int) $request->input('requestId', 0);
        if ($requestId <= 0) {
            $this->json(['code' => '422', 'message' => 'ID de solicitud invalido', 'data' => null], 422);
            return;
        }

        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            $this->json(['code' => '422', 'message' => 'Debe adjuntar un archivo', 'data' => null], 422);
            return;
        }

        $rawFile = $_FILES['file'];
        $uploadError = (int) ($rawFile['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadError !== UPLOAD_ERR_OK) {
            $this->json(['code' => '422', 'message' => 'Error al subir el archivo', 'data' => null], 422);
            return;
        }

        $tmpName  = (string) ($rawFile['tmp_name'] ?? '');
        $name     = basename((string) ($rawFile['name'] ?? ''));
        $size     = (int) ($rawFile['size'] ?? 0);
        $mimeType = (string) ($rawFile['type'] ?? '');

        if (!is_uploaded_file($tmpName)) {
            $this->json(['code' => '422', 'message' => 'Archivo no valido', 'data' => null], 422);
            return;
        }

        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            $this->json(['code' => '422', 'message' => 'El archivo no debe superar 10 MB', 'data' => null], 422);
            return;
        }

        $allowedMimes = ['application/pdf', 'application/x-pdf', 'image/jpeg', 'image/png'];
        $allowedExts  = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts, true) || !in_array($mimeType, $allowedMimes, true)) {
            $this->json(['code' => '422', 'message' => 'Solo se permiten archivos PDF, JPG o PNG', 'data' => null], 422);
            return;
        }

        $response = ServiceFactory::vacationRequestService()->uploadFileVacationRequest($requestId, [
            'name'     => $name,
            'tmp_name' => $tmpName,
            'type'     => $mimeType,
        ]);

        $this->json($response, (int) ($response['http_code'] ?? 200));
    }

    /**
     * Reads and validates uploaded files from $_FILES['files'].
     *
     * Returns a normalised list of file arrays ready to be forwarded to the API,
     * or null if any file fails validation.
     *
     * @return array<int, array{name: string, tmp_name: string, type: string}>|null
     */
    private function resolveUploadedFiles(): ?array
    {
        $allowedMimes = [
            'application/pdf',
            'application/x-pdf',
            'image/jpeg',
            'image/png',
        ];
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];

        $raw = null;
        if (isset($_FILES['files']) && is_array($_FILES['files'])) {
            $raw = $_FILES['files'];
        } elseif (isset($_FILES['Files']) && is_array($_FILES['Files'])) {
            $raw = $_FILES['Files'];
        }

        if ($raw === null) {
            return [];
        }

        // Normalise the PHP multiple-file structure into an indexed list
        $names    = is_array($raw['name'])     ? $raw['name']     : [$raw['name']];
        $tmpNames = is_array($raw['tmp_name']) ? $raw['tmp_name'] : [$raw['tmp_name']];
        $errors   = is_array($raw['error'])    ? $raw['error']    : [$raw['error']];
        $sizes    = is_array($raw['size'])     ? $raw['size']     : [$raw['size']];

        $files = [];

        foreach ($names as $i => $originalName) {
            $uploadError = (int) ($errors[$i] ?? UPLOAD_ERR_NO_FILE);

            // Skip entries where no file was selected
            if ($uploadError === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($uploadError !== UPLOAD_ERR_OK) {
                return null;
            }

            $tmpName = (string) ($tmpNames[$i] ?? '');
            $size    = (int) ($sizes[$i] ?? 0);
            $name    = basename((string) $originalName);

            if (!is_uploaded_file($tmpName)) {
                return null;
            }

            if ($size <= 0 || $size > 10 * 1024 * 1024) {
                return null;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts, true)) {
                return null;
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($tmpName);
            if (!is_string($mime) || !in_array(strtolower($mime), $allowedMimes, true)) {
                return null;
            }

            $files[] = [
                'name'     => $name,
                'tmp_name' => $tmpName,
                'type'     => strtolower($mime),
            ];
        }

        return $files;
    }

    /**
     * @param array<int, string> $allowedRoles
     */
    private function hasRole(array $allowedRoles): bool
    {
        $user = ServiceFactory::sessionManager()->getUser();
        $rolename = strtoupper(trim((string) ($user['rolename'] ?? '')));

        return in_array($rolename, $allowedRoles, true);
    }
}
