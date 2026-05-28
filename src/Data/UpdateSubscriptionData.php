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
    ) {}
}
