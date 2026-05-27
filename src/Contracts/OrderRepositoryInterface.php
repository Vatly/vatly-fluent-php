<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

/**
 * Full order repository — both read and write sides.
 *
 * Typehint this when a class needs both. Otherwise prefer the narrower
 * {@see OrderReader} or {@see OrderWriter} so collaborators advertise
 * the minimum capability they require.
 */
interface OrderRepositoryInterface extends OrderReader, OrderWriter
{
    //
}
