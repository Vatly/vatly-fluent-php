<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

use Vatly\Fluent\Data\StoreSubscriptionData;
use Vatly\Fluent\Data\UpdateSubscriptionData;

/**
 * Write-side of the subscription repository contract.
 *
 * Typehint this from webhook reactions that only persist state —
 * subscription-started, subscription-canceled. Pairs with
 * {@see SubscriptionReader}.
 */
interface SubscriptionWriter
{
    /**
     * Store a new subscription from Vatly.
     *
     * Returns `null` when the driver legitimately cannot route the store
     * (e.g. the metadata doesn't match any host record the driver knows
     * how to persist against). Built-in reactions tolerate null and skip
     * any follow-up dispatches that depend on a stored entity.
     */
    public function store(StoreSubscriptionData $data): ?SubscriptionInterface;

    /**
     * Update an existing subscription from Vatly.
     */
    public function update(SubscriptionInterface $subscription, UpdateSubscriptionData $data): SubscriptionInterface;
}
