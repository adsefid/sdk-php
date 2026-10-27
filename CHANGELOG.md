# Changelog

## Unreleased

### Breaking
- `Support\LocalIdValidator` is renamed `Support\Validator`; `Transport`, `MultipartStreamBuilder`,
  `Validator`, `TemplateParameters` and `Wire` are `@internal`.
- Unknown values are preserved: response `lineSelector` is `LineSelector|int|null`,
  `UserLine::$lineSelector` is `LineSelector|int`, template `state` is `TemplateState|string`, and an
  unrecognized template parameter type is kept as a string in `UserTemplateItem::$parameters`.
- `UserInfo::$companyName`, `$email` and `$phone` are `?string`.
- A non-2xx response without an error envelope throws `AdsefidApiException` with `rawCode` `-1` and
  name `UNKNOWN_ERROR` (was the HTTP status and `HTTP_ERROR`); a bare 429 has `rawCode` `2036` and name
  `RATE_LIMITED`. Any 429 is an `AdsefidRateLimitException`.

### Fixed
- An empty template `parameters` map is sent as `{}` (was `[]`, which the service rejects), so
  templates without parameters can be sent.
- Status lookups send ids joined by literal commas, and a GET whose path and query pass 8,000
  characters throws `AdsefidValidationException` instead of reaching the gateway's HTML 414.
- `WebhookVerifier::fromKey('')` and `new WebhookVerifier('')` are rejected, and signatures are compared
  as decoded digest bytes.
- A response with a missing or mistyped field, or an invalid datetime, throws
  `AdsefidTransportException` instead of raising a PHP warning or `ValueError`.
- A 2xx whose `data` is not an array is a malformed response.

### Added
- Request bodies use `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` (about 2x smaller for Persian text).
- `MultipartStreamBuilder::MAX_FILE_BYTES`; uploads above 15 MiB are rejected before sending.
- CI runs PHP 8.2 (locked and lowest dependencies) and 8.5.
