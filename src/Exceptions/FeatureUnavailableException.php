<?php

declare(strict_types=1);

namespace Vatly\Fluent\Exceptions;

use RuntimeException;

final class FeatureUnavailableException extends RuntimeException implements VatlyException
{
    public static function notImplementedOnApi(): self
    {
        return new self('This feature is not available yet on the Vatly API.');
    }

    public static function notImplementedOnSdk(): self
    {
        return new self('This feature is not available yet on the Vatly SDK. Feel free to submit a PR.');
    }
}
