<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User DTO.
 */
final class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $lastname,
        public readonly string $username,
        public readonly int $state,
        public readonly string $rolename,
        public readonly string $email,
        /** @var array{start_time: string, end_time: string}|null */
        public readonly ?array $workingHour
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $workingHourData = is_array($data['working_hour'] ?? null) ? $data['working_hour'] : null;
        $workingHour = $workingHourData === null
            ? null
            : [
                'start_time' => (string) ($workingHourData['start_time'] ?? ''),
                'end_time' => (string) ($workingHourData['end_time'] ?? ''),
            ];

        return new self(
            (string) ($data['id'] ?? ''),
            (string) ($data['name'] ?? ''),
            (string) ($data['lastname'] ?? ''),
            (string) ($data['username'] ?? ''),
            (int) ($data['state'] ?? 0),
            (string) ($data['rolename'] ?? ''),
            (string) ($data['email'] ?? ''),
            $workingHour
        );
    }
}
