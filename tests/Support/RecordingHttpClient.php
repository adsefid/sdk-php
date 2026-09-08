<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests\Support;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A PSR-18 client that records every request and replays a queued response.
 *
 * Injecting this is the whole test seam: `AdsefidClient` takes its PSR-18
 * client as a constructor argument, so discovery never runs under test.
 */
final class RecordingHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    private array $requests = [];

    /** @var list<ResponseInterface|ClientExceptionInterface> */
    private array $queue;

    /**
     * @param list<ResponseInterface|ClientExceptionInterface> $queue
     */
    public function __construct(array $queue)
    {
        $this->queue = $queue;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        $next = array_shift($this->queue);
        if ($next === null) {
            throw new \LogicException('RecordingHttpClient ran out of queued responses.');
        }

        if ($next instanceof ClientExceptionInterface) {
            throw $next;
        }

        return $next;
    }

    /**
     * @return list<RequestInterface>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    public function count(): int
    {
        return count($this->requests);
    }

    public function only(): RequestInterface
    {
        if (count($this->requests) !== 1) {
            throw new \LogicException(sprintf('Expected exactly 1 request, got %d.', count($this->requests)));
        }

        return $this->requests[0];
    }
}
