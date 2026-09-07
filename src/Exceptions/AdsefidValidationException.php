<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Exceptions;

/**
 * Thrown by a request DTO's constructor when a client-side pre-flight check
 * fails (e.g. an invalid `local_id`, an over-length message, an empty
 * required field) — no network call is made when this is thrown.
 */
class AdsefidValidationException extends AdsefidException
{
    /**
     * @param string $field The name of the invalid field (e.g. `local_id`, `receptors[0].message`).
     */
    public function __construct(
        string $message,
        public readonly string $field,
    ) {
        parent::__construct($message);
    }
}
