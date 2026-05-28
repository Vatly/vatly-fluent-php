<?php

declare(strict_types=1);

namespace Vatly\Fluent\Exceptions;

use RuntimeException;

final class CustomerAlreadyBound extends RuntimeException implements VatlyException
{
    public static function forHost(string $hostCustomerId, string $vatlyCustomerId): self
    {
        return new self("Host customer id '{$hostCustomerId}' is already bound to Vatly customer '{$vatlyCustomerId}'.");
    }
}
