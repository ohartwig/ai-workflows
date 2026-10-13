<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Contract\StepInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;

abstract class AbstractStep implements StepInterface
{
    public function isBlocking(): bool
    {
        return false;
    }

    public function validate(StepDefinition $definition): array
    {
        return [];
    }

    protected function getParameter(StepDefinition $definition, string $key, mixed $default = null): mixed
    {
        return $definition->parameters[$key] ?? $default;
    }

    protected function requireParameter(StepDefinition $definition, string $key): mixed
    {
        if (!array_key_exists($key, $definition->parameters)) {
            throw new \InvalidArgumentException(sprintf('Required parameter "%s" missing in step "%s"', $key, $definition->id));
        }

        return $definition->parameters[$key];
    }
}
