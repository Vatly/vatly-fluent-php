<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

/**
 * Read-side of the subscription repository contract.
 *
 * Typehint this for read-only consumers — admin dashboards, status
 * predicates inside reactions, etc. Pairs with {@see SubscriptionWriter}.
 */
interface SubscriptionReader
{
    /**
     * Find a subscription by its Vatly ID.
     */
    public function findByVatlyId(string $vatlyId): ?SubscriptionInterface;

    /**
     * Find a subscription by owner and type.
     */
    public function findByOwnerAndType(BillableInterface $owner, string $type): ?SubscriptionInterface;

    /**
     * Find all subscriptions for an owner.
     *
     * @return SubscriptionInterface[]
     */
    public function findAllByOwner(BillableInterface $owner): array;

    /**
     * Check if owner has an active subscription of a given type.
     */
    public function ownerHasActiveSubscription(BillableInterface $owner, string $type): bool;
}
