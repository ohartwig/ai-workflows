<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

interface McpServerAdapterInterface
{
    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function executeOperation(string $operation, array $arguments = []): array;
}
