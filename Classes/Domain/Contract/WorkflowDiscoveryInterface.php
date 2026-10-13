<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

use Koh\AiWorkflows\Domain\Model\WorkflowDefinition;

interface WorkflowDiscoveryInterface
{
    /**
     * @return array<string, WorkflowDefinition>
     */
    public function discoverWorkflows(string $directory): array;

    public function getWorkflow(string $directory, string $name): ?WorkflowDefinition;
}
