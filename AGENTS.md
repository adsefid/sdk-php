# AGENTS.md — adsefid/sdk (PHP)

## Scope

This repository is the PHP client SDK for the adsefid.com SMS Web Service API (package: `adsefid/sdk`). Equivalent SDKs exist for the same API in sibling repositories (`sdk-dotnet`, `sdk-js`, `sdk-python`, `sdk-go`); a behavior change here should generally be considered for parity there.

## Source of truth

The API surface (endpoints, field names, types, validation rules, enums, example payloads, webhook behavior) is defined by the published adsefid.com SMS Web Service API documentation. This SDK is verified against doc version v1.11.0. Re-read the relevant section before changing any endpoint, request/response model, or enum. The SDK follows independent Semantic Versioning from repository tags; never copy the API-document version into a tag. Record both versions in the README.

A small number of facts below are empirically observed behaviors of the live API that are easy to get wrong from a literal reading of the documentation's prose or pseudo-code. Trust these notes over an ambiguous doc reading:

- The response envelope's `error.details` is intentionally untyped (`mixed`) — its shape varies per endpoint (validation map, bulk item list, cancel-specific map, or absent). Never give it a strong class.
- Per-item `status` fields in bulk/P2P send responses are a `WebServiceCode` (doc §3.3), not a pure `WebServiceMessageStatus` — a value can legitimately be `2000+` (an error code for that one recipient, e.g. `2025 RECEPTOR_BLACKLISTED`) even though the overall response is `status: "success"`. This is why those DTOs carry `statusCode` (raw int) plus nullable `messageStatus`/`errorCode` views rather than a single hard-mapped enum. This was caught by decoding the doc's own §4.2/§4.3 examples — always decode the doc's literal example JSON when adding a new endpoint, not just synthetic values in range.
- Webhook signatures are plain Base64, not hex-then-Base64. The signature is HMAC-SHA256 over the literal string `"{timestamp}.{raw_body}"`, and the raw digest bytes are Base64-encoded directly (`base64_encode(hash_hmac('sha256', $input, $secret, true))`) — there is no intermediate hex-encoding step, even though a literal reading of some spec pseudo-code can suggest one. The header value is `"v1=" + base64signature`; compare with `hash_equals()`. See `src/Webhooks/WebhookVerifier.php`.
- `TemplateParameterType` has an undocumented third value in the wild. The documented, supported public set is `{string, number}`. The live API has been observed to also emit a `url` value for some templates; this SDK intentionally models only the two documented values — do not add support for it without first confirming it against current, documented API behavior. See `src/Enums/TemplateParameterType.php`.

## Architecture map

```
src/
├── AdsefidClient.php              Composition root: builds a Transport, exposes ->sms / ->messenger / ->user
├── ClientConfig.php               apiKey + baseUrl
├── Http/
│   ├── Transport.php              PSR-18 request building + envelope decoding + exception mapping
│   ├── MultipartStreamBuilder.php Hand-rolled multipart/form-data body builder (no PSR helper exists for this)
│   └── CsvJoiner.php              array -> CSV query param helper
├── Support/LocalIdValidator.php   All client-side pre-flight validation (local_id regex, length, count, range checks)
├── Exceptions/                    AdsefidException hierarchy (see README's error-handling section)
├── Enums/                         5 native backed enums: LineSelector, WebServiceMessageStatus, WebServiceResponseCode, TemplateState, TemplateParameterType
├── Models/Common/ErrorPayload.php Maps the error envelope's `error` object
├── Models/Sms/, Models/Messenger/, Models/User/
│                                  One Request/Response DTO class per file, hand-written toArray()/fromArray()
├── Resources/SmsResource.php, MessengerResource.php, UserResource.php
│                                  Thin methods: build request DTO -> Transport call -> decode response DTO
└── Webhooks/                      WebhookVerifier + WebhookEvent hierarchy (Receive/Status/MessengerStatus)
                                   + WebhookHeaders/WebhookEventTypes constants — use instead of typing header/type strings
```

## Adding a new endpoint

1. Read the relevant section of the pinned doc in full, including its literal example JSON — decode that exact JSON in a scratch script before writing the DTO, not just a value you invented that happens to fit the documented type.
2. Create the request DTO under `src/Models/<Area>/<Name>Request.php` (or `<Name>Request.php` for a query-string GET) — constructor does all client-side validation via `Support/LocalIdValidator.php` (add a new helper there if the doc states a new fast-fail rule), and a `toArray()` (or `toQuery()`) method.
3. Create the response DTO under `src/Models/<Area>/<Name>Response.php` — a static `fromArray()` factory and a `toArray()` method, hand-mapping every documented field.
4. **PHP requires one class per file — never combine a request and response into the same file, and never combine two operations' DTOs into one file, even if they're nearly identical.**
5. Add one method to the matching `src/Resources/<Area>Resource.php` that wires the DTO pair to the right HTTP verb/path on `Transport`.
6. If the endpoint returns a top-level JSON array (like `/v1/user/lines`), check how `Transport::decodeEnvelope()` and `UserResource::extractList()` already handle that shape before assuming you need new plumbing.

## Hard rules

- **Every change ships with tests.** `tests/` runs on PHPUnit (`make test`), namespaced
  `Adsefid\Sdk\Tests\` via `autoload-dev`. Prefer one `#[DataProvider]` table over many
  near-identical methods. Validation lives in the request DTO constructors, so a validation test
  builds the DTO and expects the exception — no HTTP client is involved.
- **Golden fixtures are shared across all five SDKs.** `tests/fixtures/` is byte-identical to the
  same tree in the sibling repositories. Never edit one in isolation: change it in all five and
  regenerate every `CHECKSUMS.txt`, or `FixturesIntegrityTest` fails.
- **PHPStan covers `tests/` too, at level 8.** It rejects an assertion it can prove tautological
  (`assertTrue(true)`, `is_subclass_of` on two literal class strings). Write a behavioural
  assertion instead, or `expectNotToPerformAssertions()` when a test's whole point is that nothing
  throws.
- **Template parameter values.** `Support\TemplateParameters::validate()` enforces the
  `string|int|float` shape at construction, and carries the `@phpstan-type` aliases. A `number`
  parameter may legitimately travel as a JSON *string* — that is how leading zeros (`'001234'`) and
  exact decimals (`'1.50'`) reach the service intact, since it substitutes a numeric string
  verbatim.
- **The webhook secret is Base64.** A webhook endpoint's secret is 32 random bytes shown
  Base64-encoded in the panel, and the service signs with the **decoded** bytes.
  `WebhookVerifier::__construct` decodes it; `WebhookVerifier::fromKey()` takes raw key bytes.
  Keying the HMAC with the UTF-8 bytes of the Base64 string does not verify against the live
  service.
- **Length limits count UTF-16 code units.** `LocalIdValidator::maxLength` uses
  `LocalIdValidator::utf16Length`, not `mb_strlen`, because that is what the service counts: a
  non-BMP character (an emoji) is one code point but two UTF-16 code units.

- **No tests, ever.** Do not add a `tests/` directory, do not add PHPUnit (or any test framework) as a dependency, not even as an empty stub. This is a deliberate project decision, not an oversight.
- **No reflection-based serialization.** Every DTO hand-writes its own `toArray()`/`fromArray()`. Do not introduce a mapper library, do not use PHP attributes for (de)serialization, do not use `ReflectionClass` to auto-map properties.
- **No magic string/int literals.** A field's set of valid values belongs in one of the 5 enums, or in a named `private const` on the relevant class (see `LocalIdValidator::LOCAL_ID_PATTERN`, `SendSingleSmsRequest::MESSAGE_MAX_LENGTH`). Do not inline `900`, `4000`, `2000`, `500`, `100`, etc. a second time — reference or duplicate the named constant, don't retype the raw number.
- **No narration comments.** PHPDoc `@param`/`@return` blocks on public API methods are expected (PHP/IDE convention) — that is not the same thing as a comment explaining what a line of code does. Only add a plain comment where a genuinely non-obvious constraint needs one (see the `readonly class` / typed-constant PHP-version notes already in the codebase).
- **Throw, never a Result object.** Every resource method throws on non-success/non-2xx. A success response is always returned directly as its typed DTO — never wrap it in an `Ok`/`Err`-style envelope. Bulk/P2P partial-success responses (HTTP 200, `status: "success"`, mixed per-item outcomes) are normal typed returns, not exceptions — see the `WebServiceCode` note above.
- **No retry logic in this SDK.** Every request is a single attempt; do not add a retry loop or retry configuration.
- Every typed constant (`const string FOO = ...`) requires PHP 8.3; this SDK targets PHP 8.2+, so class constants stay untyped (`const FOO = ...`) everywhere in this codebase — do not "fix" this by adding types back.

## Commands

```bash
composer install      # resolve psr/http-client, psr/http-factory, psr/http-message, php-http/discovery (+ require-dev: phpstan, php-cs-fixer, guzzle)
composer validate     # sanity-check composer.json
composer run lint     # PHPStan static analysis (see level note below)
composer run fmt      # PHP-CS-Fixer, auto-fix style violations
composer run fmt:check # PHP-CS-Fixer, dry-run/diff only
composer run test     # PHPUnit
php -l src/Path/To/File.php   # syntax-check a single file
find src -name '*.php' -print0 | xargs -0 -n1 php -l   # syntax-check everything
```

There is no build step and no test suite to run — a change is done when it lints clean, matches the code style, and matches the pinned doc.

### PHPStan level: 8, not 9

`phpstan.neon` runs at **level 8**. Level 9 was tried first and produces ~136 findings, almost all `cast.string`/`cast.int`/`argument.type` errors from the `mixed` values that `json_decode()` produces flowing into every DTO's `fromArray()` (`(string) $data['field']`, `(int) $item['status']`, etc.) and into the webhook event parsers. That pattern is central to this SDK's hand-written (de)serialization approach (see "No reflection-based serialization" above) — satisfying level 9 would mean adding a runtime type-assertion helper call around every single field cast across every DTO and webhook event, for no behavioral benefit (the values are already validated against the pinned API doc's documented shapes). That's substantial, mechanical rework, not bug-fixing, so this SDK stays on level 8, which passes cleanly with zero suppressions/baseline/ignores. Level 8 already caught and fixed two real issues: `AdsefidApiException` had a `$code` property colliding with the built-in `\Exception::$code` (renamed to `$responseCode`), and several `Request` DTOs had dead `?? null` fallbacks on array keys the constructors already validated as present. If you add a new DTO or webhook event and it fails level 8, fix it for real — do not lower the level further or add an ignore.
