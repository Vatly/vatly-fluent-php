<?php

declare(strict_types=1);

namespace Vatly\Fluent\Data;

use DateTimeInterface;

/**
 * Data for updating an existing subscription from Vatly.
 *
 * @immutable
 */
class UpdateSubscriptionData
{
    public function __construct(
        public ?string $planId = null,
        public ?string $name = null,
        public ?int $quantity = null,
        public ?DateTimeInterface $endsAt = null,
        public bool $clearEndsAt = false,
        /**
         * Raw Vatly status (e.g. "trialing", "active", "past_due"). Passed
         * through verbatim — drivers are responsible for mapping to their
         * host's status vocabulary. Null means "no status change".
         */
        public ?string $status = null,
        /**
         * Normalized payment-method category — see {@see \Vatly\API\Types\Mandate::$method}.
         * Null means "no change" (pair with `clearMandate: true` below to
         * explicitly remove a stored mandate).
         */
        public ?string $mandateMethod = null,
        /**
         * Customer-facing masked identifier — see {@see \Vatly\API\Types\Mandate::$maskedIdentifier}.
         * Null means "no change".
         */
        public ?string $mandateMaskedIdentifier = null,
        /**
         * When true, clears both mandate fields in the driver's storage.
         * Mirrors the `clearEndsAt` convention: a non-null `mandateMethod`
         * wins over this flag, so passing fresh mandate values is always
         * a replacement.
         *
         * Use this to explicitly remove a stored mandate (e.g. on a
         * `subscription.billing_updated` webhook where the API returns
         * `mandate: null` for a customer who revoked their payment method).
         * `SubscriptionHandle::sync()` deliberately does NOT set this when
         * the live API returns `mandate: null` — that's a known transient
         * state for freshly-subscribed customers per
         * {@see \Vatly\API\Types\Mandate::$maskedIdentifier}'s docblock,
         * and auto-clearing would wipe a freshly-stored mandate.
         */
        public bool $clearMandate = false,
    ) {}
}
