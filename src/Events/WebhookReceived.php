<?php

declare(strict_types=1);

namespace Vatly\Fluent\Events;

/**
 * Event representing a raw webhook call received from Vatly.
 *
 * @immutable
 */
class WebhookReceived
{
    /**
     * @param array<string, mixed> $object
     */
    public function __construct(
        public string $eventName,
        public string $entityId,
        public string $entityType,
        public array  $object,
        public string $createdAt,
        public bool   $testmode,
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'eventName' => $this->eventName,
            'entityId' => $this->entityId,
            'entityType' => $this->entityType,
            'object' => $this->object,
            'createdAt' => $this->createdAt,
            'testmode' => $this->testmode,
        ];
    }

    /**
     * Get the customer ID from the webhook payload, if present.
     */
    public function getCustomerId(): ?string
    {
        return $this->object['customerId'] ?? null;
    }
}
