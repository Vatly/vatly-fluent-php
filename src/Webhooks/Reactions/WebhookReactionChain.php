<?php

declare(strict_types=1);

namespace Vatly\Fluent\Webhooks\Reactions;

use Vatly\Fluent\Contracts\WebhookReactionInterface;

/**
 * A composite reaction that runs a list of inner reactions in order.
 *
 * Useful where the surrounding code wants exactly one
 * {@see WebhookReactionInterface} but the driver needs to stack many —
 * e.g. logging + audit + plugin-specific reactions. Each inner reaction's
 * `supports()` is honored independently, so non-applicable reactions
 * are skipped without affecting the rest.
 *
 * The chain itself `supports()` an event if *any* of its members do.
 *
 * Pattern borrowed from EventSauce's `MessageDispatcherChain`.
 */
final class WebhookReactionChain implements WebhookReactionInterface
{
    /** @var WebhookReactionInterface[] */
    private array $reactions;

    public function __construct(WebhookReactionInterface ...$reactions)
    {
        $this->reactions = $reactions;
    }

    public function supports(object $event): bool
    {
        foreach ($this->reactions as $reaction) {
            if ($reaction->supports($event)) {
                return true;
            }
        }

        return false;
    }

    public function handle(object $event): void
    {
        foreach ($this->reactions as $reaction) {
            if ($reaction->supports($event)) {
                $reaction->handle($event);
            }
        }
    }
}
