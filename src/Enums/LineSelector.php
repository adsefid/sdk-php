<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Enums;

enum LineSelector: int
{
    case PromotionalSendBased = 0;
    case PromotionalDeliverBased = 1;
    case BulkServiceSendBased = 2;
    case BulkServiceDeliverBased = 3;
    case CustomerClubServiceSendBased = 4;
    case CustomerClubServiceDeliverBased = 5;
}
