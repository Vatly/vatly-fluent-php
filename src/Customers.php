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
     * @throws CustomerAlreadyBound When the host id is already bound.
     */
    public function createFor(string $hostId, CustomerProfile $profile): ApiCustomer
    {
        if (($existing = $this->bindings->vatlyIdFor($hostId)) !== null) {
            throw CustomerAlreadyBound::forHost($hostId, $existing);
        }

        $customer = $this->createCustomer->execute($profile->toPayload());
        $this->bindings->bind($customer->id, $hostId);

        return $customer;
    }

    /** Anonymous: create a Vatly customer with no host attribution. */
    public function createUnattributed(CustomerProfile $profile): ApiCustomer
    {
        $customer = $this->createCustomer->execute($profile->toPayload());
        $this->bindings->record($customer->id);

        return $customer;
    }

    /** Attach (or update) a host id to an already-known Vatly customer. */
    public function attribute(string $vatlyId, string $hostId): void
    {
        $this->bindings->bind($vatlyId, $hostId);
    }

    /** Fetch the Vatly customer linked to this host id, or null. */
    public function findByHostId(string $hostId): ?ApiCustomer
    {
        $vatlyId = $this->bindings->vatlyIdFor($hostId);

        return $vatlyId !== null ? $this->getCustomer->execute($vatlyId) : null;
    }

    public function findByVatlyId(string $vatlyId): ApiCustomer
    {
        return $this->getCustomer->execute($vatlyId);
    }

    public function hostIdFor(string $vatlyId): ?string
    {
        return $this->bindings->hostIdFor($vatlyId);
    }
}
