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
    public function create(string $startDateIso, string $endDateIso, int $quantity, string $description, int $requestType, array $files = []): array
    {
        $fields = [
            'startDate'   => $startDateIso,
            'endDate'     => $endDateIso,
            'quantity'    => (string) $quantity,
            'description' => $description,
            'requestType' => (string) $requestType,
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
    public function getAll(): array
    {
        return $this->apiService->get('/api/VacationRequest/GetAll');
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllSigners(): array
    {
        return $this->apiService->get('/api/VacationRequest/GetAllSigners');
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
     * @param array{name: string, tmp_name: string, type: string} $pdfFile
     * @return array<string, mixed>
     */
    public function addSigners(int $requestId, array $signerIds, array $pdfFile): array
    {
        $fields = [
            'RequestId' => (string) $requestId,
        ];

        foreach ($signerIds as $index => $signerId) {
            $fields['SignerUserIds[' . $index . ']'] = $signerId;
        }

        return $this->apiService->postMultipart('/api/VacationRequest/AddSigners', $fields, [
            'PdfFile' => $pdfFile,
        ]);
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
    public function adjustVacationRequest(int $requestId, ?string $reason, ?int $requestCant, string $state, ?string $sing): array
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
}
