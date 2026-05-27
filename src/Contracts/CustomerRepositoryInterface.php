<?php

declare(strict_types=1);

namespace Vatly\Fluent\Contracts;

/**
 * Full customer repository — both read and write sides.
 *
 * Typehint this when a class genuinely needs both. Otherwise prefer the
 * narrower {@see CustomerReader} or {@see CustomerWriter} so collaborators
 * advertise the minimum capability they require.
 */
interface CustomerRepositoryInterface extends CustomerReader, CustomerWriter
{
    //
}
