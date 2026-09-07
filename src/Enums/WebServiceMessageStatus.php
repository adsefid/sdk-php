<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Enums;

enum WebServiceMessageStatus: int
{
    case Scheduled = 1000;
    case Sending = 1001;
    case Delivered = 1002;
    case Undelivered = 1003;
    case Canceled = 1004;
    case SentToOperator = 1005;
    case Blacklisted = 1006;
    case ProviderError = 1007;
    case PendingApproval = 1008;
    case Rejected = 1009;
    case InvalidSender = 1010;
    case InvalidAttachment = 1011;
    case ForbiddenWord = 1012;
    case LinkNotAllowed = 1013;
    case InvalidReceiver = 1014;
    case Undeliverable = 1015;
    case SenderLimitReached = 1016;
    case Unknown = 1999;
}
