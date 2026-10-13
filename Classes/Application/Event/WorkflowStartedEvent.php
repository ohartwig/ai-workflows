<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Application\Event;

final readonly class WorkflowStartedEvent
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        public int $workflowInstanceUid,
        public string $workflowName,
        public array $input,
        public int $startedBy,
    ) {}
}
