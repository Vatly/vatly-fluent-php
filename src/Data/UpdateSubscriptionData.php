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
         * Null means "no change". To represent "mandate removed", drivers should
         * still pass null here; explicit removal is a future extension if needed.
         */
        public ?string $mandateMethod = null,
        /**
         * Customer-facing masked identifier — see {@see \Vatly\API\Types\Mandate::$maskedIdentifier}.
         * Null means "no change".
         */
        public ?string $mandateMaskedIdentifier = null,
    ) {}
}
