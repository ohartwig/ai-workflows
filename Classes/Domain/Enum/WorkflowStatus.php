<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Enum;

enum WorkflowStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
