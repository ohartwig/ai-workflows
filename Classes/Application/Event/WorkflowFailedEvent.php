<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Application\Event;

final readonly class WorkflowFailedEvent
{
    public function __construct(
        public int $workflowInstanceUid,
        public string $failedStepId,
        public string $errorMessage,
    ) {}
}
