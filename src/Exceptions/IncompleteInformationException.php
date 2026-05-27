<?php

declare(strict_types=1);

namespace Vatly\Fluent\Exceptions;

use RuntimeException;

final class IncompleteInformationException extends RuntimeException implements VatlyException
{
    public static function noCheckoutItems(): self
    {
        return new self('No checkout items provided. At least one item should be set when creating a checkout.');
    }
}
