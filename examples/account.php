<?php

/**
 * Account endpoints, plus configuring the client beyond the defaults.
 *
 * Nothing here sends a message, so it is the safest example to run first
 * against a real API key.
 *
 * Usage:
 *     ADSEFID_API_KEY=... php examples/account.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Adsefid\Sdk\AdsefidClient;
use Adsefid\Sdk\ClientConfig;
use Adsefid\Sdk\Exceptions\AdsefidException;
use Adsefid\Sdk\Models\User\GetUserTemplatesRequest;
use GuzzleHttp\Client as GuzzleClient;

$apiKey = getenv('ADSEFID_API_KEY');
if ($apiKey === false) {
    fwrite(STDERR, "set ADSEFID_API_KEY\n");
    exit(1);
}

$baseUrl = getenv('ADSEFID_BASE_URL');

// The SDK never retries a request. Anything you want beyond one attempt —
// retries, a proxy, connection pooling, tracing — belongs in the PSR-18 client
// you hand over. Guzzle is used here; any PSR-18 implementation works.
$httpClient = new GuzzleClient([
    'timeout' => 20,
    'connect_timeout' => 5,
]);

$client = new AdsefidClient(
    new ClientConfig(
        apiKey: $apiKey,
        baseUrl: $baseUrl === false ? ClientConfig::DEFAULT_BASE_URL : $baseUrl,
        // Identify your own application; the SDK's default is
        // "adsefid-php/<version>".
        userAgent: 'my-billing-service/1.4 (+https://example.com)',
    ),
    $httpClient,
);

try {
    $info = $client->user->getInfo();
    printf("account %s (%s)\n", $info->name, $info->accountStatus);
    printf("  credit left: %s\n", $info->creditLeft);
    if ($info->email !== null) {
        printf("  email: %s\n", $info->email);
    }

    $lines = $client->user->getLines();
    printf("\n%d SMS line(s):\n", count($lines));
    foreach ($lines as $line) {
        printf(
            "  %-14s %-24s %-9s default selector: %s\n",
            $line->lineNumber,
            $line->lineName,
            $line->enabled ? 'enabled' : 'disabled',
            $line->lineSelector->name,
        );
    }

    $profiles = $client->user->getProfiles();
    printf("\n%d messenger profile(s):\n", count($profiles));
    foreach ($profiles as $profile) {
        printf("  %-40s %-20s %s\n", $profile->id, $profile->name, $profile->messenger);
    }

    // Templates are paged; take is capped at 100.
    $page = $client->user->getTemplates(new GetUserTemplatesRequest(skip: 0, take: 100));
    printf("\n%d template(s) (showing %d):\n", $page->total, count($page->items));
    foreach ($page->items as $item) {
        printf("  %-24s %-16s %d parameter(s)\n", $item->templateId, $item->state->value, count($item->parameters));
    }
} catch (AdsefidException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
