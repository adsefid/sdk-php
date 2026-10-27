<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Enums;

/**
 * Complete publicly documented set is `string` and `number`.
 *
 * A type this SDK does not know yet is kept as its raw string in
 * `UserTemplateItem::$parameters` instead of being rejected.
 */
enum TemplateParameterType: string
{
    case String = 'string';
    case Number = 'number';
}
