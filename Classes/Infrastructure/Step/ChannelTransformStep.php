<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Contract\SemanticDeliveryAdapterInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;

final class ChannelTransformStep extends AbstractStep
{
    public function __construct(
        private readonly SemanticDeliveryAdapterInterface $semanticDelivery,
    ) {}

    public function getType(): string
    {
        return 'channel_transform';
    }

    public function execute(StepDefinition $definition, WorkflowContext $context): StepResult
    {
        $content = (string) $this->requireParameter($definition, 'content');
        $channel = (string) $this->requireParameter($definition, 'channel');
        $options = (array) $this->getParameter($definition, 'options', []);

        try {
            $transformed = $this->semanticDelivery->transform($content, $channel, $options);

            return StepResult::success([
                'content' => $transformed,
                'channel' => $channel,
            ]);
        } catch (\Throwable $e) {
            return StepResult::error(sprintf('Channel transform failed: %s', $e->getMessage()));
        }
    }

    public function validate(StepDefinition $definition): array
    {
        $errors = [];
        if (!isset($definition->parameters['content'])) {
            $errors[] = 'Parameter "content" is required';
        }
        if (!isset($definition->parameters['channel'])) {
            $errors[] = 'Parameter "channel" is required';
        }

        return $errors;
    }
}
