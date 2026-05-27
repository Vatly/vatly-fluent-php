<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

/**
 * Write-side of the customer repository contract.
 *
 * Typehint this when you only need to persist billables — typically the
 * customer-creation flow, where a new Vatly id is being attached to an
 * existing owner. Pairs with {@see CustomerReader}.
 */
interface CustomerWriter
{
    /**
     * Save a billable entity.
     */
    public function save(BillableInterface $billable): void;
}
