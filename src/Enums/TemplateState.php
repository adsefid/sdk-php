<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Enums;

enum TemplateState: string
{
    case PendingApproval = 'pendingapproval';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
