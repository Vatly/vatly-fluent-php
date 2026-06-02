<?php

declare(strict_types=1);

namespace Vatly\Fluent\Tests\Webhooks\Reactions;

use Mockery;
use Vatly\Fluent\Contracts\OrderInterface;
use Vatly\Fluent\Contracts\OrderRepositoryInterface;
use Vatly\Fluent\Data\UpdateOrderData;
use Vatly\Fluent\Events\OrderChargebackReceived;
use Vatly\Fluent\Events\OrderChargebackReversed;
use Vatly\Fluent\Tests\TestCase;
use Vatly\Fluent\Types\LocalOrderStatus;
use Vatly\Fluent\Webhooks\Reactions\SyncOrderOnChargebackChange;

class SyncOrderOnChargebackChangeTest extends TestCase
{
    public function test_received_moves_the_order_to_chargeback(): void
    {
        $reaction = new SyncOrderOnChargebackChange(
            $this->ordersExpecting(LocalOrderStatus::CHARGEBACK),
        );

        $reaction->handle(new OrderChargebackReceived('ord_original_1', 'cb_1', 'ord_original_1'));
    }

    public function test_reversed_moves_the_order_back_to_paid(): void
    {
        $reaction = new SyncOrderOnChargebackChange(
            $this->ordersExpecting(LocalOrderStatus::PAID),
        );

        $reaction->handle(new OrderChargebackReversed('ord_original_1', 'cb_1', 'ord_original_1'));
    }

    public function test_it_does_nothing_when_the_order_is_not_tracked_locally(): void
    {
        $orders = Mockery::mock(OrderRepositoryInterface::class);
        $orders->shouldReceive('findByVatlyId')->with('ord_original_1')->once()->andReturnNull();
        $orders->shouldNotReceive('update');

        $reaction = new SyncOrderOnChargebackChange($orders);
        $reaction->handle(new OrderChargebackReceived('ord_original_1', 'cb_1', 'ord_original_1'));
    }

    private function ordersExpecting(string $expectedStatus): OrderRepositoryInterface
    {
        $order = Mockery::mock(OrderInterface::class);

        $orders = Mockery::mock(OrderRepositoryInterface::class);
        $orders->shouldReceive('findByVatlyId')->with('ord_original_1')->once()->andReturn($order);
        $orders->shouldReceive('update')->once()->with($order, Mockery::on(
            fn (UpdateOrderData $data) => $data->status === $expectedStatus,
        ))->andReturn($order);

        return $orders;
    }
}
