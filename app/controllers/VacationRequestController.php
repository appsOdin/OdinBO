<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
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

        if ($apiHttpCode === 401) {
            ServiceFactory::authService()->logout();
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
        if (!$this->hasRole(['ADMIN'])) {
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

        if ($apiHttpCode === 401) {
            ServiceFactory::authService()->logout();
            $this->redirect('/login');
            return;
        }

        $requests = $apiHttpCode === 200 && is_array($vacationResponse['data'] ?? null) ? $vacationResponse['data'] : [];

        $usersResponse = ServiceFactory::userService()->getAllUsers();
        $usersRows = is_array($usersResponse['data'] ?? null) ? $usersResponse['data'] : [];
        $users = array_values(array_filter($usersRows, static function (array $user): bool {
            return (int) ($user['state'] ?? 0) === 1;
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

        if ($endDate <= $startDate) {
            flash('danger', 'La fecha de inicio debe ser menor que la fecha de fin.');
            $this->redirect('/rrhh/solicitud-vacaciones/crear');
            return;
        }

        $days = (int) $startDate->diff($endDate)->format('%a');
        if ($days < 1 || $days > 255) {
            flash('danger', 'La cantidad de dias debe estar entre 1 y 255.');
            $this->redirect('/rrhh/solicitud-vacaciones/crear');
            return;
        }

        if ($quantityInput > 0 && $quantityInput !== $days) {
            flash('danger', 'La cantidad de dias no coincide con el rango de fechas.');
            $this->redirect('/rrhh/solicitud-vacaciones/crear');
            return;
        }

        if ($description === '' || mb_strlen($description, 'UTF-8') > 100) {
            flash('danger', 'La descripcion es obligatoria y no puede superar 100 caracteres.');
            $this->redirect('/rrhh/solicitud-vacaciones/crear');
            return;
        }

        $response = ServiceFactory::vacationRequestService()->create(
            $startDate->format('Y-m-d') . 'T00:00:00',
            $endDate->format('Y-m-d') . 'T00:00:00',
            $days,
            $description
        );

        $httpCode = (int) ($response['http_code'] ?? 0);
        $code = trim((string) ($response['code'] ?? ''));

        if ($httpCode === 401) {
            ServiceFactory::authService()->logout();
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
        if (!$this->hasRole(['ADMIN', 'USER'])) {
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
        if (!$this->hasRole(['ADMIN'])) {
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
        if (!$this->hasRole(['ADMIN', 'USER'])) {
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

        if ($apiHttpCode === 401) {
            ServiceFactory::authService()->logout();
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

        if ($apiHttpCode === 401) {
            ServiceFactory::authService()->logout();
            $this->redirect('/login');
            return;
        }

        if ($apiHttpCode !== 200) {
            flash('danger', (string) ($response['message'] ?? 'No fue posible cargar el detalle.'));
            $this->redirect('/rrhh/solicitud-vacaciones');
            return;
        }

        $detail = is_array($response['data'] ?? null) ? $response['data'] : [];

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
        if ($fileId <= 0) {
            http_response_code(422);
            echo 'ID de archivo invalido';
            exit;
        }

        $result = ServiceFactory::vacationRequestService()->downloadFile($fileId);
        $httpCode = (int) ($result['http_code'] ?? 500);

        if ($httpCode === 401) {
            ServiceFactory::authService()->logout();
            $this->redirect('/login');
            return;
        }

        if ($httpCode !== 200) {
            http_response_code($httpCode >= 400 ? $httpCode : 500);
            echo 'No fue posible descargar el archivo';
            exit;
        }

        $contentType = strtolower(trim((string) ($result['content_type'] ?? '')));
        $allowedTypes = ['application/pdf', 'application/octet-stream'];
        if (!in_array($contentType, $allowedTypes, true)) {
            // Default to PDF if the API did not send a recognised type
            $contentType = 'application/pdf';
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
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: private');
        header('X-Content-Type-Options: nosniff');

        echo $result['body'];
        exit;
    }

    public function reject(Request $request): void
    {
        if (!$this->hasRole(['ADMIN'])) {
            $this->json(['code' => '403', 'message' => 'No tiene permisos para rechazar solicitudes', 'data' => null], 403);
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

        $rejectReason = trim((string) $request->input('rejectReason', ''));
        if ($rejectReason === '') {
            $this->json(['code' => '422', 'message' => 'El motivo del rechazo es obligatorio', 'data' => null], 422);
            return;
        }

        if (mb_strlen($rejectReason, 'UTF-8') > 500) {
            $this->json(['code' => '422', 'message' => 'El motivo no puede superar 500 caracteres', 'data' => null], 422);
            return;
        }

        $detailResponse = ServiceFactory::vacationRequestService()->getDetail($requestId);
        $detailHttpCode = (int) ($detailResponse['http_code'] ?? 0);
        if ($detailHttpCode === 401) {
            ServiceFactory::authService()->logout();
            $this->json(['code' => '401', 'message' => 'Sesion expirada', 'data' => null], 401);
            return;
        }
        if ($detailHttpCode !== 200 || !is_array($detailResponse['data'] ?? null)) {
            $this->json(['code' => '422', 'message' => 'No fue posible verificar la solicitud', 'data' => null], 422);
            return;
        }
        $detail = $detailResponse['data'];
        $startDateRaw = (string) ($detail['startDate'] ?? '');
        $startDateTs = $startDateRaw !== '' ? strtotime($startDateRaw) : false;
        if ($startDateTs !== false && $startDateTs < strtotime(date('Y-m-d'))) {
            $this->json(['code' => '422', 'message' => 'No se puede rechazar una solicitud cuya fecha de inicio ya paso', 'data' => null], 422);
            return;
        }
        $stateKey = strtoupper((string) ($detail['stateKey'] ?? ''));
        if ($stateKey === 'REJECTED') {
            $this->json(['code' => '422', 'message' => 'La solicitud ya fue rechazada', 'data' => null], 422);
            return;
        }

        $response = ServiceFactory::vacationRequestService()->reject($requestId, $rejectReason);
        $httpCode = (int) ($response['http_code'] ?? 0);

        if ($httpCode === 401) {
            ServiceFactory::authService()->logout();
            $this->json(['code' => '401', 'message' => 'Sesion expirada', 'data' => null], 401);
            return;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $message = (string) ($response['data'] ?? ($response['message'] ?? 'Solicitud rechazada exitosamente'));
            $this->json(['code' => '200', 'success' => true, 'message' => $message]);
            return;
        }

        $errorMsg = (string) ($response['errorMessage'] ?? ($response['message'] ?? 'No fue posible rechazar la solicitud'));
        $this->json(['code' => (string) $httpCode, 'success' => false, 'message' => $errorMsg], $httpCode >= 400 ? $httpCode : 400);
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
