<?php

/**
 * Delivery status, cancelling scheduled messages, and reading inbound SMS.
 *
 * Status and cancel both accept message IDs (ours) and local IDs (yours) in
 * one call; their combined distinct count may not exceed 2000, which the SDK
 * checks before making the request.
 *
 * Usage:
 *     ADSEFID_API_KEY=... ADSEFID_LINE_NUMBER=3000xxxx php examples/status-and-cancel.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Adsefid\Sdk\AdsefidClient;
use Adsefid\Sdk\ClientConfig;
use Adsefid\Sdk\Exceptions\AdsefidException;
use Adsefid\Sdk\Models\Sms\CancelSmsRequest;
use Adsefid\Sdk\Models\Sms\GetReceivedSmsRequest;
use Adsefid\Sdk\Models\Sms\GetSmsStatusRequest;
use Adsefid\Sdk\Models\Sms\SendSingleSmsRequest;
use GuzzleHttp\Client as GuzzleClient;

$apiKey = getenv('ADSEFID_API_KEY');
$lineNumber = getenv('ADSEFID_LINE_NUMBER');
if ($apiKey === false || $lineNumber === false) {
    fwrite(STDERR, "set ADSEFID_API_KEY and ADSEFID_LINE_NUMBER\n");
    exit(1);
}

$client = new AdsefidClient(new ClientConfig(apiKey: $apiKey), new GuzzleClient());

try {
    // Schedule far enough ahead that there is something to cancel.
    $sendTime = new DateTimeImmutable('+2 hours');
    $sent = $client->sms->sendSingle(new SendSingleSmsRequest(
        receptor: '09120000000',
        lineNumber: $lineNumber,
        message: 'This one is scheduled, and about to be cancelled.',
        sendTime: $sendTime,
        localId: 'demo-cancel-1',
    ));
    printf("scheduled %s for %s\n", $sent->messageId, $sendTime->format(DATE_ATOM));

    // Look it up by our ID and by your own local_id at the same time.
    $status = $client->sms->getStatus(new GetSmsStatusRequest(
        messageIds: [$sent->messageId],
        localIds: ['demo-cancel-1'],
    ));
    printf("\nstatus for %d message(s):\n", count($status->receptors));
    foreach ($status->receptors as $receptor) {
        printf(
            "  %s -> %s (delivered: %s)\n",
            $receptor->messageId,
            $receptor->status?->name ?? (string) $receptor->statusCode,
            $receptor->deliveryTime?->format(DATE_ATOM) ?? 'not yet',
        );
    }

    // Cancelling reports each message separately: one already sent cannot be
    // recalled and comes back under failedToCancel.
    $cancelled = $client->sms->cancel(new CancelSmsRequest(messageIds: [$sent->messageId]));
    printf(
        "\ncancelled %d, failed to cancel %d\n",
        count($cancelled->cancelledMessages),
        count($cancelled->failedToCancel),
    );

    // Inbound messages. count must be 1..499; since filters by arrival time.
    $received = $client->sms->getReceived(new GetReceivedSmsRequest(
        lineNumber: $lineNumber,
        count: 50,
        since: new DateTimeImmutable('-1 day'),
    ));
    printf("\n%d inbound message(s) in the last 24h:\n", count($received->messages));
    foreach ($received->messages as $message) {
        printf(
            "  from %s at %s: %s\n",
            $message->sender,
            $message->receiveDate->format(DATE_ATOM),
            $message->message,
        );
    }
} catch (AdsefidException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
