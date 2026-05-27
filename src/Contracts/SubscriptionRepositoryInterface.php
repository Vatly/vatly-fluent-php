<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

/**
 * Full subscription repository — both read and write sides.
 *
 * Typehint this when a class needs both. Otherwise prefer the narrower
 * {@see SubscriptionReader} or {@see SubscriptionWriter} so collaborators
 * advertise the minimum capability they require.
 */
interface SubscriptionRepositoryInterface extends SubscriptionReader, SubscriptionWriter
{
    //
}
