<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Application\Event;

use Koh\AiWorkflows\Domain\Model\WorkflowContext;

final readonly class WorkflowCompletedEvent
{
    public function __construct(
        public int $workflowInstanceUid,
        public string $workflowName,
        public WorkflowContext $finalContext,
    ) {}
}
