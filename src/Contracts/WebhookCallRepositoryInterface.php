<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

use DateTimeInterface;

/**
 * Interface for webhook call persistence.
 */
interface WebhookCallRepositoryInterface
{
    /**
     * Record a webhook call.
     *
     * @param array<string, mixed> $object
     */
    public function record(
        string $eventName,
        string $entityId,
        string $entityType,
        array $object,
        DateTimeInterface $createdAt,
        bool $testmode,
        ?string $vatlyCustomerId = null
    ): void;

    /**
     * Clean up old webhook calls.
     */
    public function cleanUp(int $days = 7): int;
}
