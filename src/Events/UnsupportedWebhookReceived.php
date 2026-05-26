<?php

declare(strict_types=1);

namespace Vatly\Fluent\Events;

/**
 * Event representing an unsupported/unknown webhook event from Vatly.
 *
 * @immutable
 */
class UnsupportedWebhookReceived
{
    /**
     * @param array<string, mixed> $object
     */
    public function __construct(
        public string $eventName,
        public string $entityId,
        public string $entityType,
        public array $object,
        public string $createdAt,
        public bool $testmode,
    ) {
        //
    }

    public static function fromWebhook(WebhookReceived $webhook): self
    {
        return new self(
            eventName: $webhook->eventName,
            entityId: $webhook->entityId,
            entityType: $webhook->entityType,
            object: $webhook->object,
            createdAt: $webhook->createdAt,
            testmode: $webhook->testmode,
        );
    }
}
