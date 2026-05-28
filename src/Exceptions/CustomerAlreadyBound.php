<?php

declare(strict_types=1);

namespace Vatly\Fluent\Exceptions;

use RuntimeException;

final class CustomerAlreadyBound extends RuntimeException implements VatlyException
{
    public static function forHost(string $hostId, string $vatlyId): self
    {
        return new self("Host id '{$hostId}' is already bound to Vatly customer '{$vatlyId}'.");
    }
}
