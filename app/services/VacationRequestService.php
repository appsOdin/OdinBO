<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Vacation request use-cases.
 */
final class VacationRequestService
{
    public function __construct(private readonly ApiService $apiService)
    {
    }

    /**
     * @param array<int, array{name: string, tmp_name: string, type: string}> $files
     * @return array<string, mixed>
     */
    public function create(string $startDateIso, string $endDateIso, float $quantity, string $description, string $requestType, array $files = []): array
    {
        $fields = [
            'startDate'   => $startDateIso,
            'endDate'     => $endDateIso,
            'quantity'    => (string) $quantity,
            'description' => $description,
            'requestType' => $requestType,
        ];

        if ($files === []) {
            Logger::info('VacationRequest create without files', [
                'request_type' => $requestType,
            ]);

            return $this->apiService->postMultipart('/api/VacationRequest/Create', $fields, []);
        }

        $fileMap = [];
        foreach ($files as $index => $file) {
            $fileMap['Files[' . $index . ']'] = $file;
            if ($index === 0) {
                // Compatibility alias for APIs that bind a single file from "Files"
                $fileMap['Files'] = $file;
            }
        }

        Logger::info('VacationRequest create multipart files', [
            'request_type' => $requestType,
            'files_count' => count($files),
            'multipart_keys' => array_keys($fileMap),
        ]);

        return $this->apiService->postMultipart('/api/VacationRequest/Create', $fields, $fileMap);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAll(
        int $pageNumber = 1,
        int $pageSize = 10,
        string $requestType = 'VACATION',
        ?string $search = null
    ): array
    {
        return $this->apiService->post('/api/VacationRequest/GetAll', [
            'page_number' => $pageNumber,
            'page_size' => $pageSize,
            'request_type' => $requestType,
            'search' => $search,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllSigners(): array
    {
        return $this->apiService->get('/api/VacationRequest/GetAllSigners');
    }

    public function getAllLicenseTypes(): array
    {
        return $this->apiService->get('/api/VacationRequest/GetAllLicenseType');
    }

    public function getAllMedicalLeaveTypes(): array
    {
        return $this->apiService->get('/api/VacationRequest/GetAllMedicalLeaveType');
    }

    /**
     * @param array{name: string, tmp_name: string, type: string} $file
     * @param array{name: string, tmp_name: string, type: string} $softlandFile
     */
    public function createMedicalLeaveRequest(string $assignedUser, string $medicalLeaveType, string $startDate, string $endDate, int $quantity, string $description, array $file, array $softlandFile): array
    {
        return $this->apiService->postMultipart('/api/VacationRequest/CreateMedicalLeaveRequest', [
            'request_type' => REQUESTY_TYPE['MEDICAL_LEAVE'],
            'assigned_user' => $assignedUser,
            'medical_leave_type' => $medicalLeaveType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'quantity' => (string) $quantity,
            'description' => $description,
        ], ['file' => $file, 'file_softland' => $softlandFile]);
    }

    /**
     * @param array{name: string, tmp_name: string, type: string} $file
     * @param array{name: string, tmp_name: string, type: string} $softlandFile
     */
    public function extendMedicalLeaveRequest(int $requestId, string $assignedUser, string $startDate, string $endDate, int $quantity, string $description, array $file, array $softlandFile): array
    {
        return $this->apiService->postMultipart('/api/VacationRequest/ExtendMedicalLeaveRequest', [
            'requestId' => (string) $requestId,
            'assigned_user' => $assignedUser,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'quantity' => (string) $quantity,
            'description' => $description,
        ], ['file' => $file, 'file_softland' => $softlandFile]);
    }

    /**
     * @param array{name: string, tmp_name: string, type: string} $file
     */
    public function createLicenseRequest(string $assignedUser, string $licenseType, string $startDate, string $endDate, int $quantity, string $description, array $file): array
    {
        return $this->apiService->postMultipart('/api/VacationRequest/CreateLicenseRequest', [
            'request_type' => REQUESTY_TYPE['LICENSE'],
            'assigned_user' => $assignedUser,
            'license_type' => $licenseType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'quantity' => (string) $quantity,
            'description' => $description,
        ], ['files' => $file]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMy(): array
    {
        return $this->apiService->get('/api/VacationRequest/GetMy');
    }

    /**
     * @return array<string, mixed>
     */
    public function getSigners(int $requestId): array
    {
        return $this->apiService->get('/api/VacationRequest/GetSigners/' . $requestId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getFiles(int $requestId): array
    {
        return $this->apiService->get('/api/VacationRequest/GetFiles/' . $requestId);
    }

    /**
     * @param array<int, string> $signerIds
     * @param array{name: string, tmp_name: string, type: string}|null $pdfFile
     * @return array<string, mixed>
     */
    public function addSigners(int $requestId, array $signerIds, int $requestType, ?array $pdfFile = null): array
    {
        $fields = [
            'RequestId' => (string) $requestId,
            'RequestType' => (string) $requestType,
            'requestType' => (string) $requestType,
        ];

        foreach ($signerIds as $index => $signerId) {
            $fields['SignerUserIds[' . $index . ']'] = $signerId;
        }

        $files = $pdfFile === null ? [] : ['PdfFile' => $pdfFile];

        Logger::info('VacationRequest add signers multipart', [
            'request_id' => $requestId,
            'request_type' => $requestType,
            'has_pdf' => $pdfFile !== null,
        ]);

        return $this->apiService->postMultipart('/api/VacationRequest/AddSigners', $fields, $files);
    }

    /**
     * @return array<string, mixed>
     */
    public function signRequest(int $requestId, string $signatureImage): array
    {
        return $this->apiService->post('/api/VacationRequest/SignRequest', [
            'requestId' => $requestId,
            'signatureImage' => $signatureImage,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequestsToSign(): array
    {
        return $this->apiService->get('/api/VacationRequest/GetRequestsToSign');
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetail(int $requestId): array
    {
        return $this->apiService->get('/api/VacationRequest/GetDetail/' . $requestId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequestVacation(string $identification = ''): array
    {
        $normalizedIdentification = trim($identification);
        $endpoint = '/api/VacationRequest/GetRequest_Vacation';

        if ($normalizedIdentification === '') {
            return $this->apiService->get($endpoint);
        }

        return $this->apiService->get($endpoint . '/' . rawurlencode($normalizedIdentification));
    }

    /**
     * @return array<string, mixed>
     */
    public function reject(int $requestId, ?string $rejectReason, string $state, ?string $sing): array
    {
        return $this->apiService->post('/api/VacationRequest/Reject', [
            'requestId' => $requestId,
            'rejectReason' => $rejectReason,
            'state' => $state,
            'sing' => $sing,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function adjustVacationRequest(int $requestId, ?string $reason, ?float $requestCant, string $state, ?string $sing): array
    {
        return $this->apiService->post('/api/VacationRequest/AdjustVacationRequest', [
            'requestId' => $requestId,
            'reason' => $reason,
            'requestCant' => $requestCant,
            'state' => $state,
            'sing' => $sing,
        ]);
    }

    /**
     * @return array{http_code: int, body: string, content_type: string, content_disposition: string}
     */
    public function downloadFile(int $fileId): array
    {
        return $this->apiService->getFile('/api/VacationRequest/DownloadFile/' . $fileId);
    }

    /**
     * @param array{name: string, tmp_name: string, type: string} $file
     * @return array<string, mixed>
     */
    public function uploadFileVacationRequest(int $requestId, array $file): array
    {
        return $this->apiService->postMultipart('/api/VacationRequest/UploadFileVacationRequest', [
            'ResquestId' => (string) $requestId,
        ], [
            'File' => $file,
        ]);
    }

    /**
     * @param array{name: string, tmp_name: string, type: string} $file
     * @return array<string, mixed>
     */
    public function uploadFileToSign(int $requestId, array $file): array
    {
        return $this->apiService->postMultipart('/api/VacationRequest/UploadFileToSign', [
            'ResquestId' => (string) $requestId,
        ], [
            'File' => $file,
        ]);
    }
}
