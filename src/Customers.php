<?php

declare(strict_types=1);

namespace Vatly\Fluent;

use Vatly\API\Resources\Customer as ApiCustomer;
use Vatly\Fluent\Actions\CreateCustomer;
use Vatly\Fluent\Actions\GetCustomer;
use Vatly\Fluent\Contracts\CustomerBindingRepository;
use Vatly\Fluent\Exceptions\CustomerAlreadyBound;

class Customers
{
    public function __construct(
        private CreateCustomer $createCustomer,
        private GetCustomer $getCustomer,
        private CustomerBindingRepository $bindings,
    ) {
    }

    /**
     * Host-first: create a Vatly customer for a known host entity and bind.
     *
     * @throws CustomerAlreadyBound When the host customer id is already bound.
     */
    public function createFor(string $hostCustomerId, CustomerProfile $profile): ApiCustomer
    {
        if (($existing = $this->bindings->vatlyCustomerIdFor($hostCustomerId)) !== null) {
            throw CustomerAlreadyBound::forHost($hostCustomerId, $existing);
        }

        $customer = $this->createCustomer->execute($profile->toPayload());
        $this->bindings->bind($customer->id, $hostCustomerId);

        return $customer;
    }

    /** Anonymous: create a Vatly customer with no host attribution. */
    public function createUnattributed(CustomerProfile $profile): ApiCustomer
    {
        $customer = $this->createCustomer->execute($profile->toPayload());
        $this->bindings->record($customer->id);

        return $customer;
    }

    /** Attach (or update) a host customer id to an already-known Vatly customer. */
    public function attribute(string $vatlyCustomerId, string $hostCustomerId): void
    {
        $this->bindings->bind($vatlyCustomerId, $hostCustomerId);
    }

    /** Fetch the Vatly customer linked to this host customer id, or null. */
    public function findByHostCustomerId(string $hostCustomerId): ?ApiCustomer
    {
        $vatlyCustomerId = $this->bindings->vatlyCustomerIdFor($hostCustomerId);

        return $vatlyCustomerId !== null ? $this->getCustomer->execute($vatlyCustomerId) : null;
    }

    public function findByVatlyCustomerId(string $vatlyCustomerId): ApiCustomer
    {
        return $this->getCustomer->execute($vatlyCustomerId);
    }

    public function hostCustomerIdFor(string $vatlyCustomerId): ?string
    {
        return $this->bindings->hostCustomerIdFor($vatlyCustomerId);
    }
}
