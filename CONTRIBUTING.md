# Contributing

Thanks for considering a contribution to `vatly-fluent-php`. This guide is for changes **to this package itself** — bug fixes, new contracts, new reactions, doc improvements, etc.

If you're looking to *build a driver* on top of fluent (Symfony, WordPress, …), the guide for that lives in the [README](README.md#building-a-driver), not here.

## Local setup

```bash
git clone https://github.com/Vatly/vatly-fluent-php.git
cd vatly-fluent-php
composer install
composer test
composer analyse
```

Tests are PHPUnit + Mockery, fully isolated — no framework or HTTP needed. The base `TestCase` extends `PHPUnit\Framework\TestCase` and uses `MockeryPHPUnitIntegration` for cleanup.

## Design principles

These are the constraints to preserve when changing code under `src/`:

- **Zero framework imports.** No `use Illuminate\…` or `use Symfony\…` under `src/`. Drivers depend on us; we never depend on them. The webhook pipeline, the orchestrator, the reactions, and the actions are all built against framework-agnostic contracts.
- **Stateless domain logic.** Reactions, builders, the processor — none of them hold persistent state. State lives in the repository implementations the driver provides.
- **Raw API resources.** Actions return `Vatly\API\Resources\*` directly. We deliberately don't add a response-wrapper layer.
- **Immutable DTOs** for repository inputs (`StoreSubscriptionData`, `UpdateOrderData`, …) and for typed events.
- **Webhook events carry their own data.** Don't synthesise timestamps in reactions — pull them from the event, which pulled them from Vatly.

## PR process

1. Branch off `main`.
2. Add or update tests for the change.
3. Run `composer test` and `composer analyse` locally — both must be green.
4. Open the PR. CI runs the same checks across PHP 8.0 – 8.4.

## Listing your driver

If you've built a Vatly driver for a framework not yet on the driver table, please open a PR adding it to the [Looking for a Vatly integration?](README.md#looking-for-a-vatly-integration) section. To be listed, a driver should:

- Be published on Packagist
- Have a test suite that runs in CI
- Have a README that gets a new user from `composer require` to a working webhook + first subscription, in the style of [`vatly/vatly-laravel`](https://github.com/Vatly/vatly-laravel)

## License

By contributing, you agree your contributions are released under the [MIT license](LICENSE).
