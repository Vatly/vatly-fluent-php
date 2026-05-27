<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

/**
 * Read-side of the order repository contract.
 *
 * Typehint this for read-only consumers — order history views, invoice
 * URL lookups, etc. Pairs with {@see OrderWriter}.
 */
interface OrderReader
{
    /**
     * Find an order by its Vatly ID.
     */
    public function findByVatlyId(string $vatlyId): ?OrderInterface;

    /**
     * Find a single order owned by the given billable.
     *
     * Used by {@see \Vatly\Fluent\Billable::order()} to construct an
     * {@see \Vatly\Fluent\OrderHandle} for a known owner+id pair.
     *
     * @throws \Vatly\Fluent\Exceptions\InvalidOrderException When no order
     *         with the given Vatly id exists for the owner.
     */
    public function findForOwnerOrFail(BillableInterface $owner, string $vatlyId): OrderInterface;

    /**
     * Find all orders for an owner.
     *
     * @return OrderInterface[]
     */
    public function findAllByOwner(BillableInterface $owner): array;
}
