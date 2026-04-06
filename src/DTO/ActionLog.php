<?php

declare(strict_types=1);

namespace App\DTO;

final class ActionLog
{
    /**
     * @param int[] $details
     */
    private function __construct(
        public readonly \DateTime $createdAt,
        public readonly ?\DateTime $doneAt,
        public readonly ?int $executionTime,
        public readonly array $details,
        public readonly ?string $errorTrace,
    ) {}

    /**
     * @param array{
     *  created_at: string,
     *  done_at?: null|string,
     *  execution_time?: null|int|string,
     *  details?: int[],
     *  error_trace?: null|string,
     * } $data
     */
    public static function createFromArray(array $data): self
    {
        $createdAt = $data['created_at'];

        $doneAtStr = $data['done_at'] ?? null;
        $doneAt = null !== $doneAtStr ? new \DateTime($doneAtStr) : null;

        $executionTime = (isset($data['execution_time'])) ? (int) $data['execution_time'] : null;

        $details = $data['details'] ?? [];

        $errorTrace = $data['error_trace'] ?? null;

        return new self(
            new \DateTime($createdAt),
            $doneAt,
            $executionTime,
            $details,
            $errorTrace,
        );
    }
}
