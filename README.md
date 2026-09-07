# adsefid/sdk

[![CI](https://github.com/adsefid/sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/adsefid/sdk-php/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/adsefid/sdk.svg)](https://packagist.org/packages/adsefid/sdk)

A PHP client SDK for the [adsefid.com SMS Web Service](https://api-gateway.adsefid.com/Settings/Public/Documentation/webservice/pdf) REST API — SMS, Messenger (Rubika/Bale/etc.), and account/user endpoints, plus outgoing webhook signature verification.

## Requirements

- PHP 8.2+
- A [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client installed in your project, e.g. [`guzzlehttp/guzzle`](https://packagist.org/packages/guzzlehttp/guzzle) or [`symfony/http-client`](https://packagist.org/packages/symfony/http-client) (with its `psr18` adapter). `php-http/discovery` auto-detects whichever one you have installed — or you can pass your own PSR-18 client and PSR-17 factories explicitly to `AdsefidClient`.

## Install

```bash
composer require adsefid/sdk

# and a PSR-18 client, if you don't already have one:
composer require guzzlehttp/guzzle
```

## Quickstart

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Adsefid\Sdk\AdsefidClient;
use Adsefid\Sdk\ClientConfig;
use Adsefid\Sdk\Models\Sms\SendSingleSmsRequest;
use Adsefid\Sdk\Exceptions\AdsefidApiException;

$client = new AdsefidClient(
    new ClientConfig(apiKey: getenv('ADSEFID_API_KEY')),
);

try {
    $response = $client->sms->sendSingle(new SendSingleSmsRequest(
        receptor: '98912xxxxxxx',
        lineNumber: '3000xxxx',
        message: 'Hello from adsefid/sdk',
        localId: 'order-10001',
    ));

    printf(
        "Queued message %s (group %s), status=%s, cost=%d\n",
        $response->messageId,
        $response->groupId,
        $response->status->name,
        $response->cost,
    );
} catch (AdsefidApiException $e) {
    fwrite(STDERR, "adsefid API error: {$e->name} ({$e->rawCode})\n");
}
```

## Auth setup

The SDK never reads environment variables itself — read your API key explicitly and pass it in:

```php
$config = new ClientConfig(apiKey: getenv('ADSEFID_API_KEY'));
```

Every request the SDK sends carries this key as the `X-API-KEY` header, per the API's authentication scheme.

## Configuration

```php
use Adsefid\Sdk\AdsefidClient;
use Adsefid\Sdk\ClientConfig;

// Override the base URL (e.g. a staging environment):
$config = new ClientConfig(
    apiKey: getenv('ADSEFID_API_KEY'),
    baseUrl: 'https://staging.api.adsefid.com',
);

// Full dependency injection — pass your own PSR-18 client and PSR-17 factories
// instead of relying on php-http/discovery:
$client = new AdsefidClient(
    $config,
    httpClient: $myPsr18Client,
    requestFactory: $myRequestFactory,
    streamFactory: $myStreamFactory,
);

// Or let discovery find whatever PSR-18 client/factories you have installed:
$client = new AdsefidClient($config);
```

## Resource reference

| Resource | SDK method | HTTP endpoint | Notes |
|---|---|---|---|
| sms | sendSingle(SendSingleSmsRequest) | POST /v1/sms/single | |
| sms | sendBulk(SendBulkSmsRequest) | POST /v1/sms/bulk | Partial success is a normal typed return, not an exception |
| sms | sendP2P(SendP2PSmsRequest) | POST /v1/sms/p2p | Partial success is a normal typed return, not an exception |
| sms | sendTemplate(SendTemplateSmsRequest) | POST /v1/sms/template | |
| sms | getStatus(GetSmsStatusRequest) | GET /v1/sms/status | |
| sms | cancel(CancelSmsRequest) | POST /v1/sms/cancel | |
| sms | getReceived(GetReceivedSmsRequest) | GET /v1/sms/receive | |
| messenger | sendSingle(SendSingleMessengerRequest) | POST /v1/messenger/single | |
| messenger | sendBulk(SendBulkMessengerRequest) | POST /v1/messenger/bulk | Partial success is a normal typed return, not an exception |
| messenger | sendP2P(SendP2PMessengerRequest) | POST /v1/messenger/p2p | Partial success is a normal typed return, not an exception |
| messenger | uploadFile(UploadMessengerFileRequest) | POST /v1/messenger/file | Accepts a stream resource |
| messenger | cancel(CancelMessengerRequest) | POST /v1/messenger/cancel | |
| messenger | sendTemplate(SendTemplateMessengerRequest) | POST /v1/messenger/template | |
| messenger | getStatus(GetMessengerStatusRequest) | GET /v1/messenger/status | |
| user | getInfo() | GET /v1/user/info | |
| user | getLines() | GET /v1/user/lines | |
| user | getProfiles() | GET /v1/user/profiles | |
| user | getTemplates(GetUserTemplatesRequest) | GET /v1/user/templates | |

Every method **throws** on failure and returns a strongly-typed response DTO on success — there is no `Result`/`Either` wrapper. Bulk and P2P responses are still normal typed returns even when some recipients fail (HTTP 200, `status: "success"`, per-item status/error codes) — see [Partial success in bulk/P2P sends](#partial-success-in-bulkp2p-sends) below.

## Error handling

```php
use Adsefid\Sdk\Exceptions\AdsefidApiException;
use Adsefid\Sdk\Exceptions\AdsefidRateLimitException;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Exceptions\AdsefidTransportException;

try {
    $client->sms->sendSingle($request);
} catch (AdsefidValidationException $e) {
    // Failed a client-side pre-flight check (e.g. bad local_id, message too long) — no network call was made.
    echo "Invalid field '{$e->field}': {$e->getMessage()}\n";
} catch (AdsefidRateLimitException $e) {
    // Raw code 2035 (MESSAGE_LIMIT_REACHED) / 2036 (REQUEST_LIMIT_REACHED), or a bare HTTP 429.
    echo "Rate limited: {$e->name}\n";
} catch (AdsefidApiException $e) {
    // Any other status:"error" envelope, or an unexpected non-2xx response.
    echo "API error {$e->rawCode} ({$e->name}), HTTP {$e->httpStatusCode}\n";
    var_dump($e->responseCode); // ?WebServiceResponseCode — null if the server returned a code this SDK version doesn't know yet
    var_dump($e->details); // mixed — shape is endpoint-specific (validation map, item list, ...), decoded JSON or null
} catch (AdsefidTransportException $e) {
    // Network/timeout failure at the PSR-18 client level.
    echo "Transport failure: {$e->getMessage()}\n";
}
```

### Partial success in bulk/P2P sends

`SendBulkSmsResponse`, `SendP2PSmsResponse`, `SendBulkMessengerResponse`, and `SendP2PMessengerResponse` never throw for individual failed recipients. Each item's `statusCode` is the server's raw `WebServiceCode` (doc §3.3): a value in `1000-1999` means it was accepted and `messageStatus` is set (`errorCode` is `null`); a value `2000+` means that one recipient failed and `errorCode` is set instead (`messageStatus` is `null`). This mirrors the doc's own bulk example, where one receptor gets `status: 1000` and another gets `status: 2025` (`RECEPTOR_BLACKLISTED`) in the same successful response.

### Rate limiting

Codes `2035` (`MESSAGE_LIMIT_REACHED`) and `2036` (`REQUEST_LIMIT_REACHED`), and bare HTTP `429`s, surface as `AdsefidRateLimitException`.

## Enum reference

| Enum | Backing | Values |
|---|---|---|
| `Adsefid\Sdk\Enums\LineSelector` | `int` | `PromotionalSendBased` (0), `PromotionalDeliverBased` (1), `BulkServiceSendBased` (2), `BulkServiceDeliverBased` (3), `CustomerClubServiceSendBased` (4), `CustomerClubServiceDeliverBased` (5) |
| `Adsefid\Sdk\Enums\WebServiceMessageStatus` | `int` | 18 values, `1000`-`1999` (`SCHEDULED`, `SENDING`, `DELIVERED`, ... `UNKNOWN`) — see doc §3.2 |
| `Adsefid\Sdk\Enums\WebServiceResponseCode` | `int` | 46 values, `2000`-`2045` (`INTERNAL_ERROR`, `INVALID_PLAN`, ... `REJECTED`) — see doc §3.4; has an `httpStatus()` helper method |
| `Adsefid\Sdk\Enums\TemplateState` | `string` | `PendingApproval` (`pendingapproval`), `Approved` (`approved`), `Rejected` (`rejected`) |
| `Adsefid\Sdk\Enums\TemplateParameterType` | `string` | `String` (`string`), `Number` (`number`) |

`WebServiceResponseCode` on `AdsefidApiException` is nullable (`?WebServiceResponseCode $responseCode` alongside `int $rawCode`): PHP's native backed enums throw on an unrecognized value, so the SDK uses `tryFrom()` and keeps the raw int around, ensuring a response code this SDK version doesn't recognize yet never crashes your app.

## File upload example

```php
use Adsefid\Sdk\Models\Messenger\UploadMessengerFileRequest;

$handle = fopen('/path/to/brochure.pdf', 'rb');

try {
    $upload = $client->messenger->uploadFile(new UploadMessengerFileRequest(
        stream: $handle,
        filename: 'brochure.pdf',
        contentType: 'application/pdf',
    ));

    echo "Uploaded file_id: {$upload->fileId}\n";
} finally {
    fclose($handle);
}
```

## Webhook verification example

A plain PHP script handling `POST` deliveries from the platform (`receive`, `status`, `messenger.status` — doc §7):

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Adsefid\Sdk\Webhooks\WebhookVerifier;
use Adsefid\Sdk\Webhooks\WebhookHeaders;
use Adsefid\Sdk\Webhooks\ReceiveWebhookEvent;
use Adsefid\Sdk\Webhooks\StatusWebhookEvent;
use Adsefid\Sdk\Webhooks\MessengerStatusWebhookEvent;
use Adsefid\Sdk\Exceptions\AdsefidWebhookVerificationException;

$rawBody = file_get_contents('php://input');
$verifier = new WebhookVerifier(getenv('ADSEFID_WEBHOOK_SECRET'));

try {
    $event = $verifier->verifyAndParse(
        rawBody: $rawBody,
        signatureHeader: $_SERVER[WebhookHeaders::SERVER_SIGNATURE] ?? '',
        timestampHeader: $_SERVER[WebhookHeaders::SERVER_TIMESTAMP] ?? '',
    );
} catch (AdsefidWebhookVerificationException $e) {
    http_response_code(400);
    exit;
}

// Only event types this webhook endpoint is subscribed to (in your adsefid.com panel) ever
// arrive here — an endpoint subscribed to just "receive" never sees a StatusWebhookEvent.
match (true) {
    $event instanceof ReceiveWebhookEvent => processInboundSms($event),
    $event instanceof StatusWebhookEvent => processSmsStatusUpdate($event),
    $event instanceof MessengerStatusWebhookEvent => processMessengerStatusUpdate($event),
};

http_response_code(200);

function processInboundSms(ReceiveWebhookEvent $event): void
{
    foreach ($event->data as $item) {
        // $item['line_number'], $item['sender'], $item['message'], $item['receive_date']
    }
}

function processSmsStatusUpdate(StatusWebhookEvent $event): void
{
    foreach ($event->data as $item) {
        // $item['id'], $item['local_id'], $item['status_delivery'], $item['delivery_time']
    }
}

function processMessengerStatusUpdate(MessengerStatusWebhookEvent $event): void
{
    foreach ($event->data as $item) {
        // same shape as processSmsStatusUpdate()
    }
}
```

`verifyAndParse()` throws `AdsefidWebhookVerificationException` for a missing/invalid `v1=` signature, a stale timestamp (beyond `$maxAgeSeconds`, default 300), or malformed JSON — always verify before trusting the payload. Use `$_SERVER[WebhookHeaders::SERVER_ID]` + item IDs from `$event->data` to make your handler idempotent, per the doc's retry behavior (§7.7).

`WebhookHeaders` exposes every header name as a constant, both in its canonical form (`SIGNATURE`,
`TIMESTAMP`, ...) and pre-mangled for `$_SERVER` (`SERVER_SIGNATURE`, `SERVER_TIMESTAMP`, ...) —
so you never have to work out PHP's `HTTP_` + uppercase + dash-to-underscore transformation
yourself. `WebhookEventTypes` (`RECEIVE`, `STATUS`, `MESSENGER_STATUS`) does the same for the
`type` values.

You configure, per webhook endpoint, which event types it receives (in your adsefid.com panel) —
an endpoint subscribed only to `RECEIVE` will never see a `StatusWebhookEvent`, so don't assume
every deployment gets all three; handle whichever ones you've subscribed to.

## Versioning

This SDK follows [Semantic Versioning](https://semver.org/). Its version number is independent of,
and does not track, the adsefid.com Web Service API documentation's own version — the two are
different things that happen to both look like version numbers.

- **This SDK is currently at version `0.1.0`.** Package versions are set entirely by git tags on
  this repository (nothing is hardcoded in `composer.json`); the next tag to be pushed is `v0.1.0`.
- It is built against, and verified compatible with, adsefid.com Web Service API doc version
  **`v1.11.0`**. That pin is a compatibility statement, not this SDK's own version.
- This SDK's version bumps under normal semver rules, driven by changes to *this SDK*: a patch for
  a bugfix, a minor for a backward-compatible addition (e.g. a new endpoint or field), a major for
  a breaking change to this SDK's own API. The doc-version pin above only changes when someone
  re-verifies (or updates) this SDK against a newer doc revision — the two numbers move
  independently and will not generally match.

## License

Proprietary — All rights reserved.
