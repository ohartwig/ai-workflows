<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Application\Service;

use Koh\AiWorkflows\Domain\Model\WorkflowContext;

final class ExpressionResolver
{
    public function resolve(mixed $value, WorkflowContext $context): mixed
    {
        if (is_string($value)) {
            return $this->resolveString($value, $context);
        }

        if (is_array($value)) {
            return $this->resolveArray($value, $context);
        }

        return $value;
    }

    private function resolveString(string $value, WorkflowContext $context): mixed
    {
        // Exact match: entire string is a single expression — return typed value
        if (1 === preg_match('/^\{\{\s*([^}]+?)\s*\}\}$/', $value, $matches)) {
            return $context->resolveValue(trim($matches[1]));
        }

        // Inline replacements: replace each expression within the string
        $result = preg_replace_callback('/\{\{\s*([^}]+?)\s*\}\}/', function (array $matches) use ($context): string {
            $resolved = $context->resolveValue(trim($matches[1]));
            if (is_array($resolved)) {
                return json_encode($resolved, JSON_THROW_ON_ERROR);
            }

            return (string) ($resolved ?? '');
        }, $value);

        return $result ?? $value;
    }

    /**
     * @param array<string|int, mixed> $value
     *
     * @return array<string|int, mixed>
     */
    private function resolveArray(array $value, WorkflowContext $context): array
    {
        $resolved = [];
        foreach ($value as $key => $item) {
            $resolved[$key] = $this->resolve($item, $context);
        }

        return $resolved;
    }
}
