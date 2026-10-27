# adsefid/sdk

[![CI](https://github.com/adsefid/sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/adsefid/sdk-php/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/adsefid/sdk.svg)](https://packagist.org/packages/adsefid/sdk)

A PHP client for the [adsefid.com](https://adsefid.com) SMS Web Service API. It sends SMS and
Messenger messages (single, bulk, P2P and template), checks delivery status, cancels unsent
messages, reads inbound SMS, looks up account data, and verifies signed webhooks.

It needs PHP 8.2 or later and any [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client. It is
verified against API documentation **v1.14.0**. Sibling SDKs exist for .NET, Go, JavaScript and
Python with the same shape.

## Install

```bash
composer require adsefid/sdk
composer require guzzlehttp/guzzle   # or symfony/http-client, if you have no PSR-18 client yet
```

`php-http/discovery` finds the PSR-18 client and PSR-17 factories you have installed. You can also
pass your own to `AdsefidClient`.

## Quickstart

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Adsefid\Sdk\AdsefidClient;
use Adsefid\Sdk\ClientConfig;
use Adsefid\Sdk\Exceptions\AdsefidApiException;
use Adsefid\Sdk\Models\Sms\SendSingleSmsRequest;

$client = new AdsefidClient(new ClientConfig(apiKey: getenv('ADSEFID_API_KEY')));

try {
    $response = $client->sms->sendSingle(new SendSingleSmsRequest(
        receptor: '989120000000',
        lineNumber: '983000',
    lineSelector: LineSelector::BulkServiceSendBased,
        message: 'Hello from adsefid/sdk',
        localId: 'order-10001',
    ));

    printf("message %s, status=%d, cost=%g\n", $response->messageId, $response->statusCode, $response->cost);
} catch (AdsefidApiException $e) {
    fwrite(STDERR, "API error {$e->name} ({$e->rawCode})\n");
}
```

The SDK never reads environment variables. Pass the API key yourself. It goes out as the
`X-API-KEY` header on every request.

**Line selector.** An account can own several lines with the same number, each with its own
selector (see `$client->user->getLines()`). When it does, `lineSelector` is required on every SMS
send; without it the service answers `LINE_NOT_FOUND`.

## Configuration

```php
$client = new AdsefidClient(
    new ClientConfig(
        apiKey: getenv('ADSEFID_API_KEY'),
        baseUrl: 'https://api.adsefid.com',   // default; must be an absolute http(s) URL
        userAgent: 'my-service/1.0.0',        // default: adsefid-php/<SDK_VERSION>
    ),
    httpClient: new \GuzzleHttp\Client(['timeout' => 30, 'connect_timeout' => 5]),
);
```

**Set a timeout on your HTTP client.** The SDK imposes none, and a Guzzle client found by
discovery has none, so a stalled connection would wait forever. You can also pass your own
`requestFactory` and `streamFactory`. `ClientConfig` throws `AdsefidValidationException` for a
blank key, a bad URL or a bad user agent.

Request bodies are sent as unescaped UTF-8, which roughly halves the size of Persian text compared
with `\uXXXX` escapes. Money fields (`cost`, `totalCost`, `creditLeft`) are `float`, as decoded by
`json_decode`, so very long fractions can lose precision. Round for display.

## Methods

| Resource | Methods | Endpoint |
|---|---|---|
| `$client->sms` | `sendSingle`, `sendBulk`, `sendP2P`, `sendTemplate` | `POST /v1/sms/{single,bulk,p2p,template}` |
| `$client->sms` | `getStatus`, `cancel`, `getReceived` | `GET /v1/sms/status`, `POST /v1/sms/cancel`, `GET /v1/sms/receive` |
| `$client->messenger` | `sendSingle`, `sendBulk`, `sendP2P`, `sendTemplate` | `POST /v1/messenger/{single,bulk,p2p,template}` |
| `$client->messenger` | `uploadFile(UploadMessengerFileRequest)` | `POST /v1/messenger/file` |
| `$client->messenger` | `getStatus`, `cancel` | `GET /v1/messenger/status`, `POST /v1/messenger/cancel` |
| `$client->user` | `getInfo`, `getLines`, `getProfiles`, `getTemplates` | `GET /v1/user/{info,lines,profiles,templates}` |

Each method takes one request object from `Adsefid\Sdk\Models\{Sms,Messenger,User}` (except
`getInfo`, `getLines` and `getProfiles`) and returns a typed response. Request objects validate
their fields in the constructor. Cancel accepts messages that are awaiting approval, queued or
scheduled, with no refund, and at most 2,000 distinct IDs across both lists. Status lookups are GET requests, so the URL caps them at about 200 IDs per call. The SDK raises a validation error past 8,000 URL characters instead of letting the gateway answer HTTP 414.

## Bulk and P2P: partial success is not an error

A bulk or P2P call returns normally even when some recipients are rejected. Each item's
`statusCode` is either a message status (1000 to 1999) or an error code (2000 and up).
`messageStatus` and `errorCode` give the typed enum for the matching range, and are `null` for the
other range or for a code this SDK does not know yet:

```php
use Adsefid\Sdk\Models\Sms\BulkSmsReceptor;
use Adsefid\Sdk\Models\Sms\SendBulkSmsRequest;

$bulk = $client->sms->sendBulk(new SendBulkSmsRequest(
    receptors: [new BulkSmsReceptor('989120000000', localId: 'b-1'), new BulkSmsReceptor('989130000000', localId: 'b-2')],
    message: 'Hello',
    lineNumber: '983000',
    lineSelector: LineSelector::BulkServiceSendBased,
));

foreach ($bulk->receptors as $item) {
    echo $item->errorCode !== null
        ? "{$item->receptor}: rejected ({$item->errorCode->name})\n"
        : "{$item->receptor}: {$item->messageStatus?->name}\n";
}
```

Item values reach the API unchanged, so valid items still go out. If every item is rejected, the
call throws `AdsefidApiException` with `details->items` listing each rejected index. Single-send,
status, cancel and webhook items expose the same pair: a raw `statusCode` plus a nullable enum.

## Template parameters

A parameter the template declares as `number` can be sent as a number or a string. The service
substitutes a numeric string as written, so a string is the only way to keep exact digits:

```php
$client->sms->sendTemplate(new SendTemplateSmsRequest(
    templateId: 'invoice_notice',
    parameters: [
        'invoice' => '001234',  // renders 001234, not 1234
        'amount'  => '1.50',    // renders 1.50, not 1.5
        'count'   => 2,
        'rate'    => 19.99,     // a float, so rounding is possible
    ],
    receptor: '09120000000',
    lineNumber: '983000',
    lineSelector: LineSelector::BulkServiceSendBased,
));
```

Values must be `string`, `int` or a finite `float`.

## File upload

```php
use Adsefid\Sdk\Models\Messenger\UploadMessengerFileRequest;

$handle = fopen('/path/to/brochure.pdf', 'rb');

try {
    $upload = $client->messenger->uploadFile(new UploadMessengerFileRequest(
        stream: $handle,
        filename: 'brochure.pdf',
        contentType: 'application/pdf',
    ));
    echo "file_id: {$upload->fileId}\n";
} finally {
    fclose($handle);
}
```

Content is limited to 15 MiB inclusive (`MultipartStreamBuilder::MAX_FILE_BYTES`). A larger stream
throws `AdsefidValidationException` before any request is sent. The service also enforces a MIME
allowlist and answers `FileTooLarge` (2047) if it still objects to the size.

## Errors

Every method throws on failure. All exceptions extend `AdsefidException`.

| Exception | Meaning |
|---|---|
| `AdsefidValidationException` | A local check failed. No request was sent. Has `field`. |
| `AdsefidApiException` | The API returned an error envelope, or a non-2xx status without one. Has `rawCode`, nullable `responseCode`, `name`, `httpStatusCode` and nullable `details`. |
| `AdsefidRateLimitException` | Extends `AdsefidApiException`. Codes 2035 and 2036, or any HTTP 429. |
| `AdsefidTransportException` | Network failure, timeout, or a response with a missing or mistyped field. |
| `AdsefidWebhookVerificationException` | A webhook failed verification. |

```php
try {
    $client->sms->sendSingle($request);
} catch (AdsefidValidationException $e) {
    echo "Invalid '{$e->field}': {$e->getMessage()}\n";
} catch (AdsefidRateLimitException $e) {      // catch before AdsefidApiException
    echo "Rate limited: {$e->name}\n";
} catch (AdsefidApiException $e) {
    echo "API error {$e->rawCode} ({$e->name}), HTTP {$e->httpStatusCode}\n";
    // $e->responseCode is null when the server sent a code this SDK does not know yet.
    // $e->details has typed field errors (->errors) and indexed item errors (->items).
} catch (AdsefidTransportException $e) {
    echo "Transport failure: {$e->getMessage()}\n";
}
```

A non-2xx response with no error envelope has `rawCode` -1 and `name` `UNKNOWN_ERROR`. A bare 429
has `rawCode` 2036 and `name` `RATE_LIMITED`. The SDK sends each request once and never retries.
Wrap your PSR-18 client if you want backoff.

## Enums and unknown values

`LineSelector`, `WebServiceMessageStatus`, `WebServiceResponseCode`, `TemplateState` and
`TemplateParameterType` are backed enums in `Adsefid\Sdk\Enums`. A value the service adds later
never throws: an unknown line selector stays an `int`, an unknown template state stays a `string`
(`LineSelector|int`, `TemplateState|string`), and an unknown response code is `null` next to its
raw `rawCode`. `WebServiceResponseCode::httpStatus()` gives the documented HTTP status.

## Webhooks

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Adsefid\Sdk\Exceptions\AdsefidWebhookVerificationException;
use Adsefid\Sdk\Webhooks\{MessengerStatusWebhookEvent, ReceiveWebhookEvent, StatusWebhookEvent, WebhookHeaders, WebhookVerifier};

$rawBody = file_get_contents('php://input'); // verify the exact bytes, before any JSON parsing
$verifier = new WebhookVerifier(getenv('ADSEFID_WEBHOOK_SECRET'));

try {
    $event = $verifier->verifyAndParse(
        rawBody: $rawBody,
        signatureHeader: $_SERVER[WebhookHeaders::SERVER_SIGNATURE] ?? '',
        timestampHeader: $_SERVER[WebhookHeaders::SERVER_TIMESTAMP] ?? '',
    );
} catch (AdsefidWebhookVerificationException) {
    http_response_code(401); // do not say why
    exit;
}

match (true) {
    $event instanceof ReceiveWebhookEvent => handleInbound($event),          // ->data: ReceivedMessageItem[]
    $event instanceof StatusWebhookEvent => handleStatus($event),            // ->data: StatusUpdateItem[]
    $event instanceof MessengerStatusWebhookEvent => handleStatus($event),
};

http_response_code(200);
```

The panel shows the signing secret Base64-encoded, and the service signs with the decoded bytes.
Pass the secret exactly as shown to `new WebhookVerifier($secret)`, or use
`WebhookVerifier::fromKey($key)` if you store the decoded key. An empty secret or key is always
rejected. A stale timestamp (default 300 seconds, change it with `$maxAgeSeconds`) or a bad
signature throws `AdsefidWebhookVerificationException`.

`WebhookHeaders` has each header name in canonical form (`SIGNATURE`) and pre-mangled for
`$_SERVER` (`SERVER_SIGNATURE`). `WebhookEventTypes` holds the `type` values. Each endpoint only
receives the event types you subscribed it to in the panel. Deliveries can repeat, so key your
handler on `$_SERVER[WebhookHeaders::SERVER_ID]` plus the item IDs, and return a 2xx quickly.

## Using this SDK with coding agents

[AGENTS.md](AGENTS.md) holds the repository rules. These points prevent most wrong guesses:

- Pass the API key and webhook secret explicitly. The SDK reads no environment variables.
- Treat a bulk or P2P response as data, not an error. Check each item.
- Send exact numeric text (`'001234'`, `'1.50'`) as strings.
- Give `verifyAndParse` the raw request body, never a re-encoded one.
- Configure a timeout on your PSR-18 client.
- There are no retries and no automatic batching. Split large requests yourself.
- Catch `AdsefidRateLimitException` before `AdsefidApiException`, and branch on `rawCode`.

## Examples and contributing

Runnable samples are in [`examples/`](examples): `account`, `quickstart`, `bulk-and-p2p`,
`templates`, `status-and-cancel`, `messenger` and `webhook-server`. `account.php` sends nothing,
so try it first.

```bash
export ADSEFID_API_KEY=...
export ADSEFID_LINE_NUMBER=983000
php examples/quickstart.php
```

To work on the SDK: `make deps`, then `make lint`, `make build` and `make test`. See
[AGENTS.md](AGENTS.md).

## Versioning and license

Semantic Versioning, independent of the API documentation. SDK version **1.0.0**, set by git
tags, verified against API documentation **v1.14.0**. See [CHANGELOG.md](CHANGELOG.md). MIT
licensed, see [LICENSE](LICENSE).
