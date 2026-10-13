<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;

final class ConditionStep extends AbstractStep
{
    public function getType(): string
    {
        return 'condition';
    }

    public function execute(StepDefinition $definition, WorkflowContext $context): StepResult
    {
        $if = $this->requireParameter($definition, 'if');
        $equals = $this->getParameter($definition, 'equals');
        $then = $this->getParameter($definition, 'then', 'continue');
        $else = $this->getParameter($definition, 'else', 'skip');

        $value = $context->resolveValue($if);

        $conditionMet = (null !== $equals) ? ($value === $equals) : (null !== $value && '' !== $value && false !== $value);

        return StepResult::success([
            'conditionMet' => $conditionMet,
            'action' => $conditionMet ? $then : $else,
            'evaluatedValue' => $value,
        ]);
    }

    public function validate(StepDefinition $definition): array
    {
        $errors = [];
        if (!isset($definition->parameters['if'])) {
            $errors[] = 'Parameter "if" is required';
        }

        return $errors;
    }
}
