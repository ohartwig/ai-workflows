<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Application\Service;

use Koh\AiWorkflows\Application\Exception\WorkflowExecutionException;
use Koh\AiWorkflows\Domain\Contract\StepInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;

final class StepExecutor
{
    /** @var array<string, StepInterface> */
    private array $stepMap = [];

    /**
     * @param iterable<StepInterface> $steps
     */
    public function __construct(
        iterable $steps,
        private readonly ExpressionResolver $expressionResolver,
    ) {
        foreach ($steps as $step) {
            $this->stepMap[$step->getType()] = $step;
        }
    }

    public function execute(StepDefinition $definition, WorkflowContext $context): StepResult
    {
        $step = $this->stepMap[$definition->type] ?? null;
        if (null === $step) {
            throw new WorkflowExecutionException(sprintf('No step implementation found for type "%s"', $definition->type), 1711200001);
        }

        $errors = $step->validate($definition);
        if ([] !== $errors) {
            throw new WorkflowExecutionException(sprintf('Step "%s" validation failed: %s', $definition->id, implode(', ', $errors)), 1711200002);
        }

        $resolvedDefinition = new StepDefinition(
            id: $definition->id,
            type: $definition->type,
            parameters: $this->expressionResolver->resolve($definition->parameters, $context),
            output: $definition->output,
            condition: $definition->condition,
        );

        return $step->execute($resolvedDefinition, $context);
    }

    public function hasStep(string $type): bool
    {
        return isset($this->stepMap[$type]);
    }

    public function isBlocking(string $type): bool
    {
        return ($this->stepMap[$type] ?? null)?->isBlocking() ?? false;
    }
}
