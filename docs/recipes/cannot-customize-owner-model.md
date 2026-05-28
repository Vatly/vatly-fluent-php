# Cannot customize owner model

When the class you bill (User, Organization, Tenant, …) can't be modified to carry a `vatly_id` column — vendor package, sealed schema, third-party identity provider, immutable DTO — implement [`CustomerBindingRepository`](../../src/Contracts/CustomerBindingRepository.php) against a dedicated join table.

Fluent never touches your host model directly. It only asks the binding repo "what is the Vatly id for this host id?" and the reverse. So as long as you can answer those two questions, the host class itself stays untouched.

## The join table

```sql
CREATE TABLE vatly_customer_bindings (
    host_id      VARCHAR(255) NOT NULL,
    vatly_id     VARCHAR(255) NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (host_id),
    UNIQUE       (vatly_id)
);
```

If your application has multiple host types (User, Organization, Tenant) all participating as Vatly customers, add an `owner_type` column to the primary key. The repository implementation then has to know which type it's working with — usually injected at construction time.

## The repository

```php
use Vatly\Fluent\Contracts\CustomerBindingRepository;

final class JoinTableCustomerBindingRepository implements CustomerBindingRepository
{
    public function __construct(private Connection $db) {}

    public function bind(string $vatlyId, string $hostId): void
    {
        $this->db->executeStatement(
            'INSERT INTO vatly_customer_bindings (host_id, vatly_id) VALUES (?, ?)
             ON CONFLICT (host_id) DO UPDATE SET vatly_id = EXCLUDED.vatly_id',
            [$hostId, $vatlyId],
        );
    }

    public function record(string $vatlyId): void
    {
        // Anonymous-checkout flow: a Vatly customer exists but no host has been
        // attributed yet. Leave a row with an empty/null host_id, or just no-op
        // and let `attribute()` insert later. Pick what fits your reporting needs.
    }

    public function hostIdFor(string $vatlyId): ?string
    {
        return $this->db->fetchOne(
            'SELECT host_id FROM vatly_customer_bindings WHERE vatly_id = ?',
            [$vatlyId],
        ) ?: null;
    }

    public function vatlyIdFor(string $hostId): ?string
    {
        return $this->db->fetchOne(
            'SELECT vatly_id FROM vatly_customer_bindings WHERE host_id = ?',
            [$hostId],
        ) ?: null;
    }
}
```

That's the whole contract. There are no adapters, no `BillableInterface` to implement, no `getKey()`, no `save()`.

## Wire it up

Pass the repo into the `Wiring`:

```php
use Vatly\Fluent\Vatly;
use Vatly\Fluent\Wiring;

$vatly = new Vatly(new Wiring(
    config:           $config,
    subscriptions:    $subscriptionsRepo,
    orders:           $ordersRepo,
    webhookCalls:     $webhookCallsRepo,
    events:           $eventDispatcher,
    customerBindings: new JoinTableCustomerBindingRepository($db),
));
```

## Consumer side

When you want to create the Vatly customer for a known host entity:

```php
use Vatly\Fluent\CustomerProfile;

$vatly->customers()->createFor(
    hostId:  (string) $user->id(),
    profile: new CustomerProfile(email: $user->emailAddress(), name: $user->displayName()),
);
```

The binding is recorded as part of `createFor()`. From here on, `$vatly->customers()->findByHostId((string) $user->id())` returns the Vatly customer for any code that needs it.

When you receive a webhook reaction that persists a Subscription or Order, the `StoreSubscriptionData` / `StoreOrderData` carries an `?string $hostId` resolved from your binding repo. Your `SubscriptionRepositoryInterface::store()` and `OrderRepositoryInterface::store()` implementations decide what to do with it (fill the owner column, or leave null for anonymous flow).

## Notes

- **The binding repo is the only place the host id is interpreted.** Fluent treats it as an opaque string. Use whatever your domain considers stable (UUID, slug, numeric primary key) — just cast it to a string at the boundary.
- **`record()` is allowed to be a no-op.** The contract guarantees fluent calls it when a webhook arrives for a Vatly customer that isn't yet bound to anything. Drivers that don't need an explicit "anonymous customer" notion can skip it; drivers that want an audit trail for unattributed customers insert a row with a null host id.
- **Multi-tenant fan-out.** The same Vatly account can map to multiple host entities if you extend the table with an `owner_type` column and inject which type the repository is responsible for at construction time. Use a separate repository instance per host type if you have more than one.
