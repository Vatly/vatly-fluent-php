<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

use Vatly\Fluent\Data\StoreOrderData;
use Vatly\Fluent\Data\UpdateOrderData;

/**
 * Interface for order persistence.
 */
interface OrderRepositoryInterface
{
    /**
     * Find an order by its Vatly ID.
     */
    public function findByVatlyId(string $vatlyId): ?OrderInterface;

    /**
     * Find all orders for an owner.
     *
     * @return OrderInterface[]
     */
    public function findAllByOwner(BillableInterface $owner): array;

    /**
     * Store a new order from Vatly.
     */
    public function store(StoreOrderData $data): OrderInterface;

    /**
     * Update an existing order from Vatly.
     */
    public function update(OrderInterface $order, UpdateOrderData $data): OrderInterface;
}
