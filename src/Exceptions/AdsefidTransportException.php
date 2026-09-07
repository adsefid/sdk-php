<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Exceptions;

/**
 * Thrown for network, response-reading, or response-decoding failures.
 */
class AdsefidTransportException extends AdsefidException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
