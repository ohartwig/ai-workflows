<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Application\Event;

final readonly class ReviewRequestedEvent
{
    public function __construct(
        public int $workflowInstanceUid,
        public int $reviewTaskUid,
        public string $title,
    ) {}
}
