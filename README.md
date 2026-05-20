# Vatly Fluent PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vatly/vatly-fluent-php.svg?style=flat-square)](https://packagist.org/packages/vatly/vatly-fluent-php)
[![Tests](https://github.com/Vatly/vatly-fluent-php/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Vatly/vatly-fluent-php/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/vatly/vatly-fluent-php.svg?style=flat-square)](https://packagist.org/packages/vatly/vatly-fluent-php)

> **Alpha release -- under active development. Expect breaking changes.**

Shared internals for [Vatly](https://vatly.com) framework drivers. This package is the framework-agnostic core that powers driver packages like [`vatly/vatly-laravel`](https://github.com/Vatly/vatly-laravel) — webhook processing, contracts, events, DTOs, and the per-owner orchestrator (`Vatly\Fluent\Billable`) that drivers reuse so they don't reimplement the same patterns.

## Looking for a Vatly integration?

**Most users want a framework driver, not this package directly:**

| Framework | Package |
| --- | --- |
| Laravel | [`vatly/vatly-laravel`](https://github.com/Vatly/vatly-laravel) |
| Symfony, WordPress, etc. | _Planned. Want to build one? See [Building a driver](#building-a-driver) below._ |
| No framework / raw API | [`vatly/vatly-api-php`](https://github.com/Vatly/vatly-api-php) |

This package on its own doesn't persist anything, dispatch events, or read configuration — those concerns belong to a driver. You install it transitively through a driver.

## Installation

Requires PHP 8.0+ and a Vatly API key ([vatly.com](https://vatly.com)).

```bash
composer require vatly/vatly-fluent-php
```

Pin to an exact version during alpha:

```bash
composer require vatly/vatly-fluent-php:v0.5.0-alpha.1
```

## What's inside

- **Orchestrator** ([src/Billable.php](src/Billable.php), [src/BillableFactory.php](src/BillableFactory.php), [src/SubscriptionHandle.php](src/SubscriptionHandle.php)): the canonical per-owner API surface — `subscribe()`, `checkout()`, `subscribed()`, `subscription()`, `createAsVatlyCustomer()`, etc. Drivers expose this through a framework-idiomatic accessor.
- **Contracts** ([src/Contracts](src/Contracts)): `BillableInterface`, `SubscriptionInterface`, `OrderInterface`, repository interfaces (subscription / customer / order / webhook call), `EventDispatcherInterface`, `ConfigurationInterface`, `WebhookReactionInterface`.
- **Webhook pipeline** ([src/Webhooks](src/Webhooks)): `WebhookProcessor` orchestrates signature verification → event parsing → audit logging → reactions → dispatch. Built-in reactions: `SyncSubscriptionOnStarted`, `StoreOrderOnPaid`, `CancelSubscriptionOnCanceled`. Wire it in one call with `WebhookProcessorFactory::create()`.
- **Events** ([src/Events](src/Events)): typed POPOs — `OrderPaid`, `SubscriptionStarted`, `SubscriptionCanceledImmediately`, `SubscriptionCanceledWithGracePeriod`, `LocalSubscriptionCreated`, `WebhookReceived`, `UnsupportedWebhookReceived`.
- **Actions** ([src/Actions](src/Actions)): thin wrappers around [`vatly/vatly-api-php`](https://github.com/Vatly/vatly-api-php) returning raw `Vatly\API\Resources\*` objects.
- **Builders** ([src/Builders](src/Builders)): `CheckoutBuilder` and `SubscriptionBuilder` driven by a `BillableInterface`.
- **Data DTOs** ([src/Data](src/Data)): immutable inputs for repository operations.

## Building a driver

A driver is the framework-specific layer that fills in the abstractions defined here. The reference driver is [`vatly/vatly-laravel`](https://github.com/Vatly/vatly-laravel) — its `VatlyServiceProvider` and `Billable` trait are the working examples to crib from.

### Architecture

| Concern | Lives in fluent | Lives in the driver |
| --- | --- | --- |
| Webhook signature, parsing, reactions, dispatch | ✅ | — |
| Typed events (`OrderPaid`, `SubscriptionStarted`, …) | ✅ | — |
| Action wrappers around the raw API SDK | ✅ | — |
| Per-owner orchestrator (`Vatly\Fluent\Billable`) | ✅ | — |
| Repository **contracts** | ✅ | — |
| Repository **implementations** (Eloquent / Doctrine / `$wpdb` / …) | — | ✅ |
| `BillableInterface` implementation on a User/Tenant entity | — | ✅ |
| Configuration source (env, framework config) | — | ✅ |
| Event dispatcher bridge | — | ✅ |
| HTTP route for the webhook endpoint | — | ✅ |
| DI / service-container wiring | — | ✅ |
| `vatlyBillable()` accessor on the host entity | — | ✅ |

### 1. Contracts to implement

All under `Vatly\Fluent\Contracts\`:

- **`BillableInterface`** — represents a customer entity (User, Tenant, etc.). Methods: `getVatlyId()`, `setVatlyId()`, `hasVatlyId()`, `getVatlyEmail()`, `getVatlyName()`, `getKey()`, `save()`.
- **`ConfigurationInterface`** — `getApiKey()`, `getApiUrl()`, `getApiVersion()`, `getWebhookSecret()`, `isTestmode()`, `getDefaultRedirectUrlSuccess()`, `getDefaultRedirectUrlCanceled()`, `getBillableModel()`.
- **`SubscriptionRepositoryInterface`** — `findByVatlyId()`, `findByOwnerAndType()`, `findAllByOwner()`, `ownerHasActiveSubscription()`, `store(StoreSubscriptionData)`, `update(SubscriptionInterface, UpdateSubscriptionData)`.
- **`CustomerRepositoryInterface`** — `findByVatlyId()`, `findByVatlyIdOrFail()`, `save(BillableInterface)`.
- **`OrderRepositoryInterface`** — `findByVatlyId()`, `findAllByOwner()`, `store(StoreOrderData)`, `update(OrderInterface, UpdateOrderData)`.
- **`WebhookCallRepositoryInterface`** — `record(…)` (audit log), `cleanUp(int $days)`.
- **`EventDispatcherInterface`** — single method `dispatch(object $event)`. Bridge to your framework's event bus or PSR-14.

Your driver also needs concrete `SubscriptionInterface` and `OrderInterface` implementations (typically your ORM models).

### 2. Wiring sequence

In your driver's bootstrap (`ServiceProvider`, bundle config, plugin init), wire in this order:

1. Bind your `ConfigurationInterface` implementation.
2. Construct a `Vatly\API\VatlyApiClient` and configure it from your `ConfigurationInterface`.
3. Bind your repository implementations to the four repository contracts.
4. Bind your event dispatcher implementation to `EventDispatcherInterface`.
5. Build a `WebhookProcessor` via the factory:

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

If you need to swap out the `SignatureVerifier` or `WebhookEventFactory`, construct `WebhookProcessor` directly with all the reactions instead.

6. Register a `BillableFactory` singleton with the shared dependencies:

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

7. In your User/Tenant trait or base class, expose the orchestrator:

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

The canonical per-owner API surface lives on `Vatly\Fluent\Billable` (returned by `vatlyBillable()`). Drivers typically expose that surface through framework idioms:

- **Cashier-style proxy methods** on the entity trait — `$user->subscribe()`, `$user->subscribed('default')`, `$user->subscription('default')`, `$user->checkout()` — that delegate to `vatlyBillable()->X()`. See [vatly-laravel's `Billable` trait](https://github.com/Vatly/vatly-laravel/blob/main/src/Billable.php) for the reference.
- **ORM-native relations** for collection-style access (`$user->subscriptions`) that fluent can't provide.
- **Audit/admin views, console commands, fakes** — purely driver-level.

## Testing

```bash
composer test
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for local setup, design principles, and the PR process. If you've built a Vatly driver for a framework not yet covered, that's also where to PR yourself onto the driver table above.

## License

MIT
