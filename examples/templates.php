<?php

/**
 * Listing templates and sending one, with exact numeric parameter values.
 *
 * A parameter the template declares as `number` may be sent either as a JSON
 * number or as a JSON string, and the service substitutes a numeric string
 * verbatim. That is the only way to keep a value's exact digits: '001234'
 * keeps its leading zeros and '1.50' its trailing zero, where the numbers
 * 1234 and 1.5 would not.
 *
 * Usage:
 *     ADSEFID_API_KEY=... ADSEFID_LINE_NUMBER=983000XXX php examples/templates.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Adsefid\Sdk\AdsefidClient;
use Adsefid\Sdk\ClientConfig;
use Adsefid\Sdk\Enums\TemplateParameterType;
use Adsefid\Sdk\Enums\TemplateState;
use Adsefid\Sdk\Exceptions\AdsefidException;
use Adsefid\Sdk\Models\Sms\SendTemplateSmsRequest;
use Adsefid\Sdk\Models\User\GetUserTemplatesRequest;
use GuzzleHttp\Client as GuzzleClient;

$apiKey = getenv('ADSEFID_API_KEY');
$lineNumber = getenv('ADSEFID_LINE_NUMBER');
if ($apiKey === false || $lineNumber === false) {
    fwrite(STDERR, "set ADSEFID_API_KEY and ADSEFID_LINE_NUMBER\n");
    exit(1);
}

$client = new AdsefidClient(new ClientConfig(apiKey: $apiKey), new GuzzleClient());

try {
    $page = $client->user->getTemplates(new GetUserTemplatesRequest(
        state: TemplateState::Approved,
        take: 100,
    ));

    if ($page->items === []) {
        fwrite(STDERR, "no approved templates on this account — create one in the adsefid.com panel first\n");
        exit(1);
    }

    printf("%d approved template(s):\n", $page->total);
    foreach ($page->items as $item) {
        printf("  %-24s %s\n", $item->templateId, $item->content);
    }

    $template = $page->items[0];

    // Build one value per declared parameter. A `number` parameter gets a
    // string here so its exact form survives.
    $parameters = [];
    foreach ($template->parameters as $name => $type) {
        $parameters[$name] = $type === TemplateParameterType::String ? 'Ali' : '1.50';
    }

    $result = $client->sms->sendTemplate(new SendTemplateSmsRequest(
        templateId: $template->templateId,
        parameters: $parameters,
        receptor: '09120000000',
        lineNumber: $lineNumber,
        expiryDate: new DateTimeImmutable('+10 minutes'),
    ));

    printf("\nsent %s: %s\n", $result->messageId, $result->status->name);
    printf("  rendered: %s\n", $result->message);
    printf("  parameters echoed back: %s\n", json_encode($result->parameters));
} catch (AdsefidException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

// Which representation to reach for, shown without sending anything.
echo "\nchoosing a parameter value:\n";
foreach ([
    'an ordinary count' => 2,
    'a price where float rounding is fine' => 19.99,
    'an invoice number whose leading zeros matter' => '001234',
    'an amount that must render as exactly 1.50' => '1.50',
] as $why => $value) {
    printf("  %-48s -> %s\n", $why, json_encode($value));
}
