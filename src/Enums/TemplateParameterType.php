<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Enums;

/**
 * Complete publicly documented set is `string` and `number`.
 *
 * Note: the live server has been observed to also emit an undocumented
 * `url` case. It is intentionally NOT exposed here since it is not part
 * of the documented public contract; revisit if the doc is updated.
 */
enum TemplateParameterType: string
{
    case String = 'string';
    case Number = 'number';
}
