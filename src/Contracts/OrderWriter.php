<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

use Vatly\Fluent\Data\StoreOrderData;
use Vatly\Fluent\Data\UpdateOrderData;

/**
 * Write-side of the order repository contract.
 *
 * Typehint this from webhook reactions that only persist orders —
 * order-paid, order-refunded. Pairs with {@see OrderReader}.
 */
interface OrderWriter
{
    /**
     * Store a new order from Vatly.
     */
    public function store(StoreOrderData $data): OrderInterface;

    /**
     * Update an existing order from Vatly.
     */
    public function update(OrderInterface $order, UpdateOrderData $data): OrderInterface;
}
