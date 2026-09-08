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
 *     ADSEFID_API_KEY=... ADSEFID_LINE_NUMBER=3000xxxx php examples/bulk-and-p2p.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Adsefid\Sdk\AdsefidClient;
use Adsefid\Sdk\ClientConfig;
use Adsefid\Sdk\Exceptions\AdsefidException;
use Adsefid\Sdk\Models\Sms\SendBulkSmsRequest;
use Adsefid\Sdk\Models\Sms\SendP2PSmsRequest;
use GuzzleHttp\Client as GuzzleClient;

$apiKey = getenv('ADSEFID_API_KEY');
$lineNumber = getenv('ADSEFID_LINE_NUMBER');
if ($apiKey === false || $lineNumber === false) {
    fwrite(STDERR, "set ADSEFID_API_KEY and ADSEFID_LINE_NUMBER\n");
    exit(1);
}

$client = new AdsefidClient(new ClientConfig(apiKey: $apiKey), new GuzzleClient());

/**
 * @param array{message_id: ?string, receptor: string, local_id: ?string, statusCode: int} $item
 */
function report(array $item): void
{
    $label = $item['local_id'] ?? '-';

    if ($item['statusCode'] >= 2000) {
        printf("  %-14s (%s) FAILED with code %d\n", $item['receptor'], $label, $item['statusCode']);

        return;
    }

    printf(
        "  %-14s (%s) accepted as %s, status %d\n",
        $item['receptor'],
        $label,
        $item['message_id'] ?? '-',
        $item['statusCode'],
    );
}

try {
    // One identical message to many receptors. local_id is your own handle: it
    // comes back here and on the status webhook, so you can match a delivery
    // report to your own record without storing our message IDs.
    $bulk = $client->sms->sendBulk(new SendBulkSmsRequest(
        receptors: [
            ['receptor' => '09120000000', 'local_id' => 'maint-1'],
            ['receptor' => '09120000001', 'local_id' => 'maint-2'],
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
            ['receptor' => '09120000000', 'message' => 'Hi Ali, your order #1001 shipped.', 'local_id' => 'ship-1001'],
            ['receptor' => '09120000001', 'message' => 'Hi Reza, your order #1002 shipped.', 'local_id' => 'ship-1002'],
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
