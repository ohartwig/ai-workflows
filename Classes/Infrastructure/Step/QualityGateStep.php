<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Contract\ContentIntelligenceAdapterInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;

final class QualityGateStep extends AbstractStep
{
    public function __construct(
        private readonly ContentIntelligenceAdapterInterface $contentIntelligence,
    ) {}

    public function getType(): string
    {
        return 'quality_gate';
    }

    public function execute(StepDefinition $definition, WorkflowContext $context): StepResult
    {
        $content = (string) $this->requireParameter($definition, 'content');
        $checks = (array) $this->getParameter($definition, 'checks', []);
        $threshold = (float) $this->getParameter($definition, 'threshold', 0.8);

        $result = $this->contentIntelligence->analyzeContent($content, $checks);

        $score = (float) ($result['score'] ?? 0.0);
        $passed = $result['passed'] ?? ($score >= $threshold);

        if (true === $passed) {
            return StepResult::success([
                'score' => $score,
                'checks' => $result['checks'] ?? [],
                'passed' => true,
            ]);
        }

        return StepResult::error(
            sprintf('Quality gate failed: score %.2f below threshold %.2f', $score, $threshold),
            [
                'score' => $score,
                'checks' => $result['checks'] ?? [],
                'passed' => false,
            ],
        );
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
