<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Contract\McpServerAdapterInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;

final class McpOperationStep extends AbstractStep
{
    public function __construct(
        private readonly McpServerAdapterInterface $mcpServer,
    ) {}

    public function getType(): string
    {
        return 'mcp_operation';
    }

    public function execute(StepDefinition $definition, WorkflowContext $context): StepResult
    {
        $operation = (string) $this->requireParameter($definition, 'operation');
        $arguments = (array) $this->getParameter($definition, 'arguments', []);

        $result = $this->mcpServer->executeOperation($operation, $arguments);

        if (($result['success'] ?? false) !== true) {
            return StepResult::error($result['error'] ?? 'MCP operation failed', $result);
        }

        return StepResult::success($result);
    }

    public function validate(StepDefinition $definition): array
    {
        $errors = [];
        if (!isset($definition->parameters['operation'])) {
            $errors[] = 'Parameter "operation" is required';
        }

        return $errors;
    }
}
