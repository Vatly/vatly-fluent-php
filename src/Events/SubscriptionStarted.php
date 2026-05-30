<?php

declare(strict_types=1);

namespace Vatly\Fluent\Events;

use Vatly\API\Resources\Subscription as ApiSubscription;

/**
 * Event representing a subscription being started at Vatly.
 *
 * Built by {@see \Vatly\Fluent\Webhooks\WebhookEventFactory} via a follow-up
 * `GetSubscription` call against the webhook's `entityId`, so the dispatched
 * event carries the mandate summary that isn't on the webhook payload.
 *
 * @immutable
 */
class SubscriptionStarted
{
    public const VATLY_EVENT_NAME = 'subscription.started';

    public const DEFAULT_TYPE = 'default';

    public function __construct(
        public string $customerId,
        public string $subscriptionId,
        public string $planId,
        public string $type,
        public string $name,
        public int $quantity,
        /**
         * Normalized payment-method category — see {@see \Vatly\API\Types\Mandate::$method}.
         * `null` when no mandate is on file yet at webhook time (the API briefly
         * returns `mandate: null` for freshly-subscribed customers).
         */
        public ?string $mandateMethod = null,
        /**
         * Customer-facing masked identifier — see {@see \Vatly\API\Types\Mandate::$maskedIdentifier}.
         */
        public ?string $mandateMaskedIdentifier = null,
    ) {
        //
    }

    /**
     * Build from the enriched API resource fetched by
     * {@see \Vatly\Fluent\Webhooks\WebhookEventFactory::createFromWebhook()}.
     */
    public static function fromApiSubscription(ApiSubscription $subscription): self
    {
        return new self(
            customerId: $subscription->customerId ?? '',
            subscriptionId: $subscription->id,
            planId: $subscription->subscriptionPlanId,
            type: self::DEFAULT_TYPE,
            name: $subscription->name,
            quantity: $subscription->quantity,
            mandateMethod: $subscription->mandate?->method,
            mandateMaskedIdentifier: $subscription->mandate?->maskedIdentifier,
        );
    }

    /**
     * Sparse, webhook-payload-only build kept for tests and callers who don't
     * want to fetch the full API resource. Mandate fields stay null.
     */
    public static function fromWebhook(WebhookReceived $webhook): self
    {
        return new self(
            customerId: $webhook->object['customerId'],
            subscriptionId: $webhook->entityId,
            planId: $webhook->object['subscriptionPlanId'],
            type: self::DEFAULT_TYPE,
            name: $webhook->object['name'],
            quantity: $webhook->object['quantity'],
        );
    }
}
