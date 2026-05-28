<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

/**
 * Persists the link between a Vatly customer id and a host-side customer id.
 *
 * Bidirectional but bidirectionally-optional. A row may be recorded with
 * only a Vatly id (anonymous-checkout flow); the host id can be attributed
 * later. Driver implementations decide where the link is stored — a column
 * on the host's user table, a dedicated join table, user meta, etc.
 */
interface CustomerBindingRepository
{
    /** Bind a Vatly customer to a host entity. Idempotent. */
    public function bind(string $vatlyId, string $hostId): void;

    /** Record a Vatly customer with no host attribution yet. Idempotent. */
    public function record(string $vatlyId): void;

    /** Host id bound to this Vatly id, or null. */
    public function hostIdFor(string $vatlyId): ?string;

    /** Vatly id bound to this host id, or null. */
    public function vatlyIdFor(string $hostId): ?string;
}
