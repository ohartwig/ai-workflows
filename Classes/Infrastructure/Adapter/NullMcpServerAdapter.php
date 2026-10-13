<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Adapter;

use Koh\AiWorkflows\Domain\Contract\McpServerAdapterInterface;

final class NullMcpServerAdapter implements McpServerAdapterInterface
{
    public function executeOperation(string $operation, array $arguments = []): array
    {
        return [
            'success' => false,
            'error' => 'MCP Server extension not installed',
            'operation' => $operation,
        ];
    }
}
