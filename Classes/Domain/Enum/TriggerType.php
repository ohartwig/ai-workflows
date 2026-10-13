<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Enum;

enum TriggerType: string
{
    case Manual = 'manual';
    case Schedule = 'schedule';
    case Event = 'event';
}
