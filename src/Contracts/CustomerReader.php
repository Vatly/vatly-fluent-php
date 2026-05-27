<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

/**
 * Read-side of the customer repository contract.
 *
 * Typehint this when you only need to look billables up — e.g. listing
 * views, read-only dashboards. Pairs with {@see CustomerWriter}; both
 * are combined into {@see CustomerRepositoryInterface} for callers that
 * need both sides.
 */
interface CustomerReader
{
    /**
     * Find a billable by its Vatly customer ID.
     */
    public function findByVatlyId(string $vatlyId): ?BillableInterface;

    /**
     * Find a billable by its Vatly customer ID or fail.
     *
     * @throws \Vatly\Fluent\Exceptions\InvalidCustomerException
     */
    public function findByVatlyIdOrFail(string $vatlyId): BillableInterface;
}
