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
     */
    public function store(StoreSubscriptionData $data): SubscriptionInterface;

    /**
     * Update an existing subscription from Vatly.
     */
    public function update(SubscriptionInterface $subscription, UpdateSubscriptionData $data): SubscriptionInterface;
}
