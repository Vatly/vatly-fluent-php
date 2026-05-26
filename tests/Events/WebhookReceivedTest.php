<?php

declare(strict_types=1);

namespace Vatly\Fluent\Tests\Events;

use Vatly\Fluent\Events\WebhookReceived;
use Vatly\Fluent\Tests\TestCase;

class WebhookReceivedTest extends TestCase
{
    public function test_it_can_be_instantiated_with_all_properties(): void
    {
        $event = new WebhookReceived(
            eventName: 'subscription.started',
            entityId: 'sub_123',
            entityType: 'subscription',
            object: ['customerId' => 'cus_456'],
            createdAt: '2024-01-15T10:00:00Z',
            testmode: true,
        );

        $this->assertSame('subscription.started', $event->eventName);
        $this->assertSame('sub_123', $event->entityId);
        $this->assertSame('subscription', $event->entityType);
        $this->assertSame(['customerId' => 'cus_456'], $event->object);
        $this->assertSame('2024-01-15T10:00:00Z', $event->createdAt);
        $this->assertTrue($event->testmode);
    }

    public function test_it_converts_to_array(): void
    {
        $event = new WebhookReceived(
            eventName: 'subscription.started',
            entityId: 'sub_123',
            entityType: 'subscription',
            object: [],
            createdAt: '2024-01-15T10:00:00Z',
            testmode: false,
        );

        $array = $event->toArray();

        $this->assertArrayHasKey('eventName', $array);
        $this->assertArrayHasKey('entityId', $array);
        $this->assertArrayHasKey('entityType', $array);
        $this->assertArrayHasKey('object', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('testmode', $array);
        $this->assertSame('subscription.started', $array['eventName']);
    }

    public function test_it_extracts_customer_id_from_object(): void
    {
        $event = new WebhookReceived(
            eventName: 'subscription.started',
            entityId: 'sub_123',
            entityType: 'subscription',
            object: ['customerId' => 'cus_456'],
            createdAt: '2024-01-15T10:00:00Z',
            testmode: false,
        );

        $this->assertSame('cus_456', $event->getCustomerId());
    }

    public function test_it_returns_null_when_customer_id_not_present(): void
    {
        $event = new WebhookReceived(
            eventName: 'test.event',
            entityId: 'res_123',
            entityType: 'resource',
            object: [],
            createdAt: '2024-01-15T10:00:00Z',
            testmode: false,
        );

        $this->assertNull($event->getCustomerId());
    }
}
