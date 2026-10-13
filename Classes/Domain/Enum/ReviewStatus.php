<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Enum;

enum ReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Timeout = 'timeout';
}
