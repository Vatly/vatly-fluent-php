<?php

declare(strict_types=1);

namespace Vatly\Fluent\Webhooks\Reactions;

use Vatly\Fluent\Contracts\OrderRepositoryInterface;
use Vatly\Fluent\Contracts\WebhookReactionInterface;
use Vatly\Fluent\Data\UpdateOrderData;
use Vatly\Fluent\Events\OrderChargebackReceived;
use Vatly\Fluent\Events\OrderChargebackReversed;
use Vatly\Fluent\Types\LocalOrderStatus;

/**
 * Propagates a chargeback onto the original order's *local* status — the
 * chargeback companion to {@see SyncOrderOnRefundChange}.
 *
 * Vatly's public Order resource always reports `paid` (it collapses the
 * internal `chargeback` state upstream), and there's no `order.chargeback`
 * status webhook. But the chargeback events carry the original order id, so a
 * consumer that wants the dispute reflected locally can derive it:
 *
 *   - `order.chargeback_received` → original order moves to {@see LocalOrderStatus::CHARGEBACK}
 *   - `order.chargeback_reversed` → original order moves back to {@see LocalOrderStatus::PAID}
 *
 * Registered only when an {@see OrderRepositoryInterface} and a chargeback
 * repository are both wired (paired with {@see SyncChargebackOnStatusChange}).
 * Find-or-skip: it only updates an order it already tracks.
 *
 * @immutable
 */
class SyncOrderOnChargebackChange implements WebhookReactionInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
    ) {}

    public function supports(object $event): bool
    {
        return $event instanceof OrderChargebackReceived
            || $event instanceof OrderChargebackReversed;
    }

    public function handle(object $event): void
    {
        if (! $event instanceof OrderChargebackReceived
            && ! $event instanceof OrderChargebackReversed) {
            return;
        }

        $order = $this->orders->findByVatlyId($event->originalOrderId);

        if ($order === null) {
            return;
        }

        $newStatus = $event instanceof OrderChargebackReceived
            ? LocalOrderStatus::CHARGEBACK
            : LocalOrderStatus::PAID;

        $this->orders->update($order, new UpdateOrderData(status: $newStatus));
    }
}
