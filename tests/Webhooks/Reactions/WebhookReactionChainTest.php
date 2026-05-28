<?php

declare(strict_types=1);

namespace Vatly\Fluent\Tests\Webhooks\Reactions;

use Mockery;
use stdClass;
use Vatly\Fluent\Contracts\WebhookReactionInterface;
use Vatly\Fluent\Tests\TestCase;
use Vatly\Fluent\Webhooks\Reactions\WebhookReactionChain;

class WebhookReactionChainTest extends TestCase
{
    public function test_chain_supports_event_when_any_member_does(): void
    {
        $event = new stdClass();

        $supportingReaction = Mockery::mock(WebhookReactionInterface::class);
        $supportingReaction->shouldReceive('supports')->with($event)->andReturn(true);

        $nonSupportingReaction = Mockery::mock(WebhookReactionInterface::class);
        $nonSupportingReaction->shouldReceive('supports')->with($event)->andReturn(false);

        $chain = new WebhookReactionChain($nonSupportingReaction, $supportingReaction);

        $this->assertTrue($chain->supports($event));
    }

    public function test_chain_does_not_support_event_when_no_members_do(): void
    {
        $event = new stdClass();

        $a = Mockery::mock(WebhookReactionInterface::class);
        $a->shouldReceive('supports')->with($event)->andReturn(false);

        $b = Mockery::mock(WebhookReactionInterface::class);
        $b->shouldReceive('supports')->with($event)->andReturn(false);

        $chain = new WebhookReactionChain($a, $b);

        $this->assertFalse($chain->supports($event));
    }

    public function test_handle_runs_supporting_members_in_order(): void
    {
        $event = new stdClass();
        $sequence = [];

        $a = Mockery::mock(WebhookReactionInterface::class);
        $a->shouldReceive('supports')->with($event)->andReturn(true);
        $a->shouldReceive('handle')->with($event)->andReturnUsing(function () use (&$sequence) {
            $sequence[] = 'a';
        });

        $b = Mockery::mock(WebhookReactionInterface::class);
        $b->shouldReceive('supports')->with($event)->andReturn(true);
        $b->shouldReceive('handle')->with($event)->andReturnUsing(function () use (&$sequence) {
            $sequence[] = 'b';
        });

        $chain = new WebhookReactionChain($a, $b);
        $chain->handle($event);

        $this->assertSame(['a', 'b'], $sequence);
    }

    public function test_handle_skips_non_supporting_members(): void
    {
        $event = new stdClass();

        $supporting = Mockery::mock(WebhookReactionInterface::class);
        $supporting->shouldReceive('supports')->with($event)->andReturn(true);
        $supporting->shouldReceive('handle')->once()->with($event);

        $nonSupporting = Mockery::mock(WebhookReactionInterface::class);
        $nonSupporting->shouldReceive('supports')->with($event)->andReturn(false);
        $nonSupporting->shouldNotReceive('handle');

        $chain = new WebhookReactionChain($nonSupporting, $supporting);
        $chain->handle($event);
    }

    public function test_empty_chain_is_inert(): void
    {
        $chain = new WebhookReactionChain();

        $this->assertFalse($chain->supports(new stdClass()));
        $chain->handle(new stdClass());  // no-op, no exception
        $this->addToAssertionCount(1);
    }
}
