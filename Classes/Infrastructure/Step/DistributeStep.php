<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;

final class DistributeStep extends AbstractStep
{
    public function getType(): string
    {
        return 'distribute';
    }

    public function execute(StepDefinition $definition, WorkflowContext $context): StepResult
    {
        $content = $this->requireParameter($definition, 'content');
        $targets = (array) $this->getParameter($definition, 'targets', []);

        $results = [];
        foreach ($targets as $target) {
            $results[$target] = [
                'status' => 'distributed',
                'timestamp' => time(),
            ];
        }

        return StepResult::success([
            'content' => $content,
            'distributions' => $results,
        ]);
    }

    public function validate(StepDefinition $definition): array
    {
        $errors = [];
        if (!isset($definition->parameters['content'])) {
            $errors[] = 'Parameter "content" is required';
        }

        return $errors;
    }
}
