<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Contract\AiClientInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;

final class AiGenerateStep extends AbstractStep
{
    public function __construct(
        private readonly AiClientInterface $aiClient,
    ) {}

    public function getType(): string
    {
        return 'ai_generate';
    }

    public function execute(StepDefinition $definition, WorkflowContext $context): StepResult
    {
        $prompt = (string) $this->requireParameter($definition, 'prompt');
        $options = (array) $this->getParameter($definition, 'options', []);

        try {
            $generatedContent = $this->aiClient->generate($prompt, $options);

            return StepResult::success(['content' => $generatedContent]);
        } catch (\Throwable $e) {
            // Sanitize exception message to prevent leaking sensitive data (e.g. API keys from URLs)
            $sanitizedMessage = preg_replace(
                '#(https?://[^\s]*[?&])(api[_-]?key|token|secret|password|authorization)=[^\s&]*#i',
                '$1$2=[REDACTED]',
                $e->getMessage(),
            ) ?? 'AI generation failed';

            return StepResult::error(sprintf('AI generation failed: %s', $sanitizedMessage));
        }
    }

    public function validate(StepDefinition $definition): array
    {
        $errors = [];
        if (!isset($definition->parameters['prompt'])) {
            $errors[] = 'Parameter "prompt" is required';
        }

        return $errors;
    }
}
