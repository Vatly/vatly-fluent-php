<?php

declare(strict_types=1);

namespace Vatly\Fluent\Exceptions;

use RuntimeException;

final class InvalidWebhookSignatureException extends RuntimeException implements VatlyException
{
    public static function missingSignature(): self
    {
        return new self('Missing Vatly webhook signature.');
    }

    public static function invalidSignature(): self
    {
        return new self('Invalid Vatly webhook signature.');
    }
}
