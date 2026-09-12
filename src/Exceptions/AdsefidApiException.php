<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Exceptions;

use Adsefid\Sdk\Enums\WebServiceResponseCode;
use Adsefid\Sdk\Models\Common\ApiErrorDetails;

/**
 * Thrown when the adsefid.com API returns a `status: "error"` envelope, an
 * unexpected non-2xx response, or a malformed success envelope.
 */
class AdsefidApiException extends AdsefidException
{
    /**
     * @param int                      $rawCode       The raw `error.code` from the response (or the HTTP status when no envelope could be decoded).
     * @param ?WebServiceResponseCode  $responseCode  `$rawCode` mapped to the enum, or `null` if this SDK version doesn't recognize the code yet — always trust `$rawCode` over this.
     * @param string                   $name          The server's `error.name` (e.g. `INVALID_LINE`), or a synthetic name (`HTTP_ERROR`, `MALFORMED_RESPONSE`) when no envelope was decoded.
     * @param ?ApiErrorDetails         $details       Structured `error.details`, or `null` when absent.
     */
    public function __construct(
        string $message,
        public readonly int $rawCode,
        public readonly ?WebServiceResponseCode $responseCode,
        public readonly string $name,
        public readonly int $httpStatusCode,
        public readonly ?ApiErrorDetails $details,
    ) {
        parent::__construct($message);
    }
}
