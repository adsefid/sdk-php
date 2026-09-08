<?php

/**
 * The Messenger resource end to end: upload an attachment, send it, check status.
 *
 * Messenger sends go through a "profile" configured in your adsefid.com panel
 * rather than an SMS line, and allow a longer body (4000 characters against
 * SMS's 900). File upload is the only multipart endpoint in the API; the SDK
 * streams the resource you hand it rather than buffering the whole file.
 *
 * Usage:
 *     ADSEFID_API_KEY=... php examples/messenger.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Adsefid\Sdk\AdsefidClient;
use Adsefid\Sdk\ClientConfig;
use Adsefid\Sdk\Exceptions\AdsefidException;
use Adsefid\Sdk\Models\Messenger\GetMessengerStatusRequest;
use Adsefid\Sdk\Models\Messenger\SendSingleMessengerRequest;
use Adsefid\Sdk\Models\Messenger\UploadMessengerFileRequest;
use GuzzleHttp\Client as GuzzleClient;

$apiKey = getenv('ADSEFID_API_KEY');
if ($apiKey === false) {
    fwrite(STDERR, "set ADSEFID_API_KEY\n");
    exit(1);
}

$client = new AdsefidClient(new ClientConfig(apiKey: $apiKey), new GuzzleClient());

try {
    $profiles = $client->user->getProfiles();
    if ($profiles === []) {
        fwrite(STDERR, "no messenger profiles on this account — add one in the adsefid.com panel first\n");
        exit(1);
    }

    printf("%d messenger profile(s):\n", count($profiles));
    foreach ($profiles as $profile) {
        printf("  %-40s %-20s %s\n", $profile->id, $profile->name, $profile->messenger);
    }

    // Any readable stream resource works; in a real program this is usually
    // fopen('/path/to/file.pdf', 'r').
    $stream = fopen('php://temp', 'r+');
    if ($stream === false) {
        throw new RuntimeException('could not open a temporary stream');
    }
    fwrite($stream, "Statement for September 2026\nTotal: 1,250,000 IRR\n");
    rewind($stream);

    $uploaded = $client->messenger->uploadFile(new UploadMessengerFileRequest(
        stream: $stream,
        filename: 'statement.txt',
        contentType: 'text/plain',
    ));
    printf("\nuploaded attachment as file_id %s\n", $uploaded->fileId);

    $sent = $client->messenger->sendSingle(new SendSingleMessengerRequest(
        message: 'Your statement is attached.',
        receptor: '09120000000',
        profile: $profiles[0]->id,
        fileId: $uploaded->fileId,
        localId: 'statement-2026-09',
    ));
    printf("\nsent %s via %s: %s (cost %s)\n", $sent->messageId, $sent->messenger, $sent->status->name, $sent->cost);

    $status = $client->messenger->getStatus(new GetMessengerStatusRequest(messageIds: [$sent->messageId]));
    foreach ($status->receptors as $receptor) {
        printf("  %s -> %s\n", $receptor['message_id'], $receptor['status']->name ?? 'unknown');
    }
} catch (AdsefidException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
