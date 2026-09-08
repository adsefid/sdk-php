<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Adsefid\Sdk\AdsefidClient;
use Adsefid\Sdk\ClientConfig;
use Adsefid\Sdk\Exceptions\AdsefidApiException;
use Adsefid\Sdk\Exceptions\AdsefidRateLimitException;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Models\Sms\SendSingleSmsRequest;
use GuzzleHttp\Client as GuzzleClient;

$apiKey = getenv('ADSEFID_API_KEY');

if ($apiKey === false || $apiKey === '') {
    fwrite(STDERR, "Missing ADSEFID_API_KEY environment variable.\n");
    exit(1);
}

$client = new AdsefidClient(
    new ClientConfig(apiKey: $apiKey),
    httpClient: new GuzzleClient(),
);

try {
    $response = $client->sms->sendSingle(new SendSingleSmsRequest(
        receptor: '09120000000',
        lineNumber: '3000505',
        message: 'Hello from the Adsefid PHP SDK quickstart example.',
    ));

    printf(
        "Message sent: group_id=%s message_id=%s status=%s cost=%g segments=%d\n",
        $response->groupId,
        $response->messageId,
        $response->status->name,
        $response->cost,
        $response->segmentCount,
    );
} catch (AdsefidValidationException $exception) {
    fwrite(STDERR, sprintf("Validation error on field \"%s\": %s\n", $exception->field, $exception->getMessage()));
    exit(1);
} catch (AdsefidRateLimitException $exception) {
    fwrite(STDERR, sprintf("Rate limited (code %d): %s\n", $exception->rawCode, $exception->getMessage()));
    exit(1);
} catch (AdsefidApiException $exception) {
    fwrite(STDERR, sprintf(
        "API error \"%s\" (http=%d, code=%d): %s\n",
        $exception->name,
        $exception->httpStatusCode,
        $exception->rawCode,
        $exception->getMessage(),
    ));
    exit(1);
}
