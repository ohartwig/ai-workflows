<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Application\Event;

use Koh\AiWorkflows\Domain\Model\StepResult;

final readonly class StepCompletedEvent
{
    public function __construct(
        public int $workflowInstanceUid,
        public string $stepId,
        public string $stepType,
        public StepResult $result,
    ) {}
}
