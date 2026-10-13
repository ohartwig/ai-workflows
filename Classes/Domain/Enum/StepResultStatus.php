<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Enum;

enum StepResultStatus: string
{
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Blocked = 'blocked';
}
