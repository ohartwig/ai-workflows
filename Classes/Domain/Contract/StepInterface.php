<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;

interface StepInterface
{
    public function getType(): string;

    public function execute(StepDefinition $definition, WorkflowContext $context): StepResult;

    public function isBlocking(): bool;

    /**
     * @return list<string>
     */
    public function validate(StepDefinition $definition): array;
}
