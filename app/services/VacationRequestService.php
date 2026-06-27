<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Vacation request use-cases.
 */
final class VacationRequestService
{
    public function __construct(private readonly ApiService $apiService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $startDateIso, string $endDateIso, int $quantity, string $description, int $requestType): array
    {
        return $this->apiService->post('/api/VacationRequest/Create', [
            'startDate' => $startDateIso,
            'endDate' => $endDateIso,
            'quantity' => $quantity,
            'description' => $description,
            'requestType' => $requestType,
        ]);
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
    public function reject(int $requestId, string $rejectReason): array
    {
        return $this->apiService->post('/api/VacationRequest/Reject', [
            'requestId' => $requestId,
            'rejectReason' => $rejectReason,
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
