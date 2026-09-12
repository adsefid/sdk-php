<?php

/**
 * Bulk and P2P SMS sends, and how to read a partial success.
 *
 * Both endpoints answer HTTP 200 even when some receptors failed, so a call
 * that did not throw still needs its per-item results inspected. `statusCode`
 * is the raw number the service sent: below 2000 it is a delivery status,
 * 2000 and above it is an error code for that one receptor. `messageStatus`
 * and `errorCode` are the typed views of the same number, and exactly one of
 * them is non-null.
 *
 * Usage:
 *     ADSEFID_API_KEY=... ADSEFID_LINE_NUMBER=983000XXX php examples/bulk-and-p2p.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Adsefid\Sdk\AdsefidClient;
use Adsefid\Sdk\ClientConfig;
use Adsefid\Sdk\Exceptions\AdsefidException;
use Adsefid\Sdk\Models\Sms\BulkSmsReceptor;
use Adsefid\Sdk\Models\Sms\BulkSmsReceptorResult;
use Adsefid\Sdk\Models\Sms\P2PSmsMessage;
use Adsefid\Sdk\Models\Sms\P2PSmsMessageResult;
use Adsefid\Sdk\Models\Sms\SendBulkSmsRequest;
use Adsefid\Sdk\Models\Sms\SendP2PSmsRequest;
use Adsefid\Sdk\Support\WebServiceCode;
use GuzzleHttp\Client as GuzzleClient;

$apiKey = getenv('ADSEFID_API_KEY');
$lineNumber = getenv('ADSEFID_LINE_NUMBER');
if ($apiKey === false || $lineNumber === false) {
    fwrite(STDERR, "set ADSEFID_API_KEY and ADSEFID_LINE_NUMBER\n");
    exit(1);
}

$client = new AdsefidClient(new ClientConfig(apiKey: $apiKey), new GuzzleClient());

function report(BulkSmsReceptorResult|P2PSmsMessageResult $item): void
{
    $label = $item->localId ?? '-';

    if ($item->errorCode !== null || WebServiceCode::isErrorCode($item->statusCode)) {
        printf("  %-14s (%s) FAILED with code %d (%s)\n", $item->receptor, $label, $item->statusCode, $item->errorCode?->name ?? 'unknown');

        return;
    }

    printf(
        "  %-14s (%s) accepted as %s, status %s\n",
        $item->receptor,
        $label,
        $item->messageId ?? '-',
        $item->messageStatus?->name ?? (string) $item->statusCode,
    );
}

try {
    // One identical message to many receptors. local_id is your own handle: it
    // comes back here and on the status webhook, so you can match a delivery
    // report to your own record without storing our message IDs.
    $bulk = $client->sms->sendBulk(new SendBulkSmsRequest(
        receptors: [
            new BulkSmsReceptor('09120000000', localId: 'maint-1'),
            new BulkSmsReceptor('09120000001', localId: 'maint-2'),
        ],
        message: 'Scheduled maintenance tonight from 01:00 to 03:00.',
        lineNumber: $lineNumber,
    ));

    printf("\nbulk group %s: %d receptors, cost %s\n", $bulk->groupId, $bulk->totalCount, $bulk->totalCost);
    foreach ($bulk->receptors as $receptor) {
        report($receptor);
    }
    printf("  status histogram: %s\n", json_encode($bulk->counts));

    // A different message per receptor, in one request.
    $p2p = $client->sms->sendP2P(new SendP2PSmsRequest(
        messages: [
            new P2PSmsMessage('09120000000', 'Hi Ali, your order #1001 shipped.', localId: 'ship-1001'),
            new P2PSmsMessage('09120000001', 'Hi Reza, your order #1002 shipped.', localId: 'ship-1002'),
        ],
        lineNumber: $lineNumber,
    ));

    printf("\np2p group %s: cost %s\n", $p2p->groupId, $p2p->totalCost);
    foreach ($p2p->messages as $message) {
        report($message);
    }
} catch (AdsefidException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
