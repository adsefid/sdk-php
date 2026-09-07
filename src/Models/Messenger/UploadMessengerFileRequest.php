<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class UploadMessengerFileRequest
{
    /**
     * @param resource $stream A readable PHP stream resource (e.g. from `fopen()`).
     *
     * @throws AdsefidValidationException if `filename` or `contentType` is empty.
     * @throws AdsefidValidationException if `$stream` is not a PHP stream resource.
     */
    public function __construct(
        public readonly mixed $stream,
        public readonly string $filename,
        public readonly string $contentType,
    ) {
        LocalIdValidator::requireNonEmpty($this->filename, 'filename');
        LocalIdValidator::requireNonEmpty($this->contentType, 'content_type');

        if (!is_resource($this->stream)) {
            throw new AdsefidValidationException('UploadMessengerFileRequest::$stream must be a PHP stream resource.', 'stream');
        }
    }
}
