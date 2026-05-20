# Contributing & Driver Author Guide

This package is the shared core that powers framework-specific Vatly drivers. Most readers want one of two things:

- **Building a driver** for a framework that isn't covered yet (Symfony, WordPress, etc.) → start at [Building a driver](#building-a-driver).
- **Contributing to this package itself** (bug fix, new contract, new reaction) → jump to [Working on this package](#working-on-this-package).

If you're an application developer looking to use Vatly, you almost certainly want a driver, not this package directly. See the [README](README.md#looking-for-a-vatly-integration) for the list.

## Architecture

A driver is the framework-specific layer that fills in the abstractions this package defines. Concretely:

| Concern | Lives in fluent | Lives in the driver |
| --- | --- | --- |
| Webhook signature, parsing, reactions, dispatch | ✅ | — |
| Typed events (`OrderPaid`, `SubscriptionStarted`, …) | ✅ | — |
| Action wrappers around the raw API SDK | ✅ | — |
| Repository **contracts** (subscription, order, customer, webhook call) | ✅ | — |
| Repository **implementations** (Eloquent / Doctrine / WP `$wpdb` / …) | — | ✅ |
| `BillableInterface` implementation on a User/Tenant entity | — | ✅ |
| Configuration source (env, `config()`, framework config) | — | ✅ |
| Event dispatcher bridge | — | ✅ |
| HTTP route for the webhook endpoint | — | ✅ |
| DI / service container wiring | — | ✅ |

The reference driver is [`vatly/vatly-laravel`](https://github.com/Vatly/vatly-laravel) — read its `VatlyServiceProvider` for a concrete wiring example.

## Building a driver

### 1. The contracts you must implement

All under `Vatly\Fluent\Contracts\`:

- **`BillableInterface`** — represents a customer entity (your User, Tenant, etc.). Methods: `getVatlyId()`, `setVatlyId()`, `hasVatlyId()`, `getVatlyEmail()`, `getVatlyName()`, `getKey()`, `save()`.
- **`ConfigurationInterface`** — exposes API credentials and defaults. Methods: `getApiKey()`, `getApiUrl()`, `getApiVersion()`, `getWebhookSecret()`, `isTestmode()`, `getDefaultRedirectUrlSuccess()`, `getDefaultRedirectUrlCanceled()`, `getBillableModel()`.
- **`SubscriptionRepositoryInterface`** — `findByVatlyId()`, `findByOwnerAndType()`, `findAllByOwner()`, `ownerHasActiveSubscription()`, `store(StoreSubscriptionData)`, `update(SubscriptionInterface, UpdateSubscriptionData)`.
- **`CustomerRepositoryInterface`** — `findByVatlyId()`, `findByVatlyIdOrFail()`, `save(BillableInterface)`.
- **`OrderRepositoryInterface`** — `findByVatlyId()`, `findAllByOwner()`, `store(StoreOrderData)`, `update(OrderInterface, UpdateOrderData)`.
- **`WebhookCallRepositoryInterface`** — `record(…)` (audit log), `cleanUp(int $days)`.
- **`EventDispatcherInterface`** — single method `dispatch(object $event)`. Bridge to your framework's event bus or PSR-14.

Your driver also needs concrete `SubscriptionInterface` and `OrderInterface` implementations (typically your ORM models).

### 2. The wiring sequence

In your driver's bootstrap (`ServiceProvider`, bundle config, plugin init), wire in this order:

1. Bind your `ConfigurationInterface` implementation.
2. Construct a `Vatly\API\VatlyApiClient` and configure it from your `ConfigurationInterface`.
3. Bind your repository implementations to the four repository contracts.
4. Bind your event dispatcher implementation to `EventDispatcherInterface`.
5. Build a `WebhookProcessor`. The easiest path is `WebhookProcessorFactory::create()`, which wires the standard reactions for you:

```php
use Vatly\Fluent\Webhooks\WebhookProcessorFactory;

$processor = WebhookProcessorFactory::create(
    config: $config,
    subscriptions: $subscriptionRepository,
    orders: $orderRepository,
    webhookCalls: $webhookCallRepository,
    dispatcher: $eventDispatcher,
    additionalReactions: [
        // Optional custom reactions implementing WebhookReactionInterface
    ],
);
```

If you need to swap out the `SignatureVerifier` or `WebhookEventFactory`, construct `WebhookProcessor` directly:

```php
use Vatly\Fluent\Webhooks\WebhookProcessor;
use Vatly\Fluent\Webhooks\Reactions\SyncSubscriptionOnStarted;
use Vatly\Fluent\Webhooks\Reactions\StoreOrderOnPaid;
use Vatly\Fluent\Webhooks\Reactions\CancelSubscriptionOnCanceled;

new WebhookProcessor(
    signatureVerifier: $signatureVerifier,
    eventFactory: $eventFactory,
    repository: $webhookCallRepository,
    dispatcher: $eventDispatcher,
    webhookSecret: $config->getWebhookSecret() ?? '',
    reactions: [
        new SyncSubscriptionOnStarted($subscriptionRepository, $eventDispatcher),
        new CancelSubscriptionOnCanceled($subscriptionRepository),
        new StoreOrderOnPaid($orderRepository),
    ],
);
```

6. Register a `BillableFactory` singleton with the shared dependencies. This is what your `$entity->vatlyBillable()` accessor calls to construct a per-owner `Vatly\Fluent\Billable`:

```php
use Vatly\Fluent\BillableFactory;

$factory = new BillableFactory(
    subscriptions: $subscriptionRepository,
    customers: $customerRepository,
    orders: $orderRepository,
    config: $config,
    createCheckoutAction: new CreateCheckout($apiClient),
    createCustomerAction: new CreateCustomer($apiClient),
    getCustomerAction: new GetCustomer($apiClient),
    getSubscriptionAction: new GetSubscription($apiClient),
    swapSubscriptionPlanAction: new SwapSubscriptionPlan($apiClient),
    cancelSubscriptionAction: new CancelSubscription($apiClient),
    createBillingUpdateLinkAction: new CreateSubscriptionBillingUpdateLink($apiClient),
);
```

In your User/Tenant trait or base class, expose it:

```php
public function vatlyBillable(): \Vatly\Fluent\Billable
{
    return $container->get(BillableFactory::class)->forOwner($this);
}
```

### 3. The webhook endpoint

Expose a single POST route in your framework. In the handler:

```php
try {
    $processor->handle(
        payload: $request->getRawBody(),       // raw, not deserialized
        signature: $request->getHeader('X-Vatly-Signature') ?? '',
    );
    return new Response(status: 201);
} catch (InvalidWebhookSignatureException) {
    return new Response(status: 403);
}
```

Return `2xx` on success, `403` on signature mismatch. Anything else and Vatly will retry.

### 4. Application-facing ergonomics

The canonical per-owner API surface lives in fluent on `Vatly\Fluent\Billable` — `subscribe()`, `checkout()`, `subscribed()`, `subscription()`, `createAsVatlyCustomer()`, etc. Drivers expose that surface through their framework idioms:

- **A trait, base class, or accessor** so the application's User/Tenant entity gets a `vatlyBillable()` method returning a `Vatly\Fluent\Billable`. Optionally add Cashier-style proxy methods (`$user->subscribe()`, `$user->subscribed()`) that delegate to `vatlyBillable()->X()` — see [vatly-laravel's `Billable` trait](https://github.com/Vatly/vatly-laravel/blob/main/src/Billable.php) for the reference example.
- **Eloquent/Doctrine/etc. relations** for collection-style access (`$user->subscriptions`) that fluent can't provide — those stay driver-side.
- **Audit/admin views, console commands, fakes** — purely driver-level.

### 5. Listing your driver

Once your driver is on Packagist and has tests + a README, open a PR against this repo's `README.md` adding it to the "Looking for a Vatly integration?" table.

## Working on this package

### Local setup

```bash
git clone https://github.com/Vatly/vatly-fluent-php.git
cd vatly-fluent-php
composer install
composer test
composer analyse
```

Tests are PHPUnit + Mockery, fully isolated — no framework or HTTP needed. The base `TestCase` extends `PHPUnit\Framework\TestCase` and uses `MockeryPHPUnitIntegration` for cleanup.

### Design principles

- **Zero framework imports.** No `use Illuminate\…` or `use Symfony\…` under `src/`. Drivers depend on us; we never depend on them.
- **Stateless domain logic.** Reactions, builders, the processor — none of them hold state. State lives in the repository implementations the driver provides.
- **Raw API resources.** Actions return `Vatly\API\Resources\*` directly. We deliberately don't add a response wrapper layer — that's a driver-level concern if needed at all.
- **Immutable DTOs** for repository inputs (`StoreSubscriptionData`, `UpdateOrderData`, …).

### PR process

1. Branch off `main`.
2. Add or update tests for the change.
3. Run `composer test` and `composer analyse` locally.
4. Open the PR. CI runs the same checks.


## License

MIT
