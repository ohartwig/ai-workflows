<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Model;

use Koh\AiWorkflows\Domain\Enum\StepResultStatus;

/**
 * Immutable execution context for a single workflow run.
 *
 * Every mutating operation (`with*`) returns a new instance with the change
 * applied. Callers (most prominently `WorkflowEngine::executeSteps()`) must
 * re-assign the returned value:
 *
 *     $context = $context->withVariable('foo', 'bar');
 *     $context = $context->withStepResult($stepId, $result);
 *
 * This lets the engine snapshot the context at any point (e.g. to persist it
 * when a step blocks) without worrying that a later mutation will rewrite the
 * snapshot it just saved.
 */
final readonly class WorkflowContext
{
    /**
     * @param array<string, mixed>      $input
     * @param array<string, mixed>      $variables
     * @param array<string, StepResult> $stepResults
     */
    public function __construct(
        private array $input = [],
        private array $variables = [],
        private array $stepResults = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getInput(): array
    {
        return $this->input;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function withInput(array $input): self
    {
        return new self(
            input: $input,
            variables: $this->variables,
            stepResults: $this->stepResults,
        );
    }

    public function getVariable(string $key, mixed $default = null): mixed
    {
        return $this->variables[$key] ?? $default;
    }

    public function withVariable(string $key, mixed $value): self
    {
        $variables = $this->variables;
        $variables[$key] = $value;

        return new self(
            input: $this->input,
            variables: $variables,
            stepResults: $this->stepResults,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    public function withStepResult(string $stepId, StepResult $result): self
    {
        $stepResults = $this->stepResults;
        $stepResults[$stepId] = $result;

        return new self(
            input: $this->input,
            variables: $this->variables,
            stepResults: $stepResults,
        );
    }

    public function getStepResult(string $stepId): ?StepResult
    {
        return $this->stepResults[$stepId] ?? null;
    }

    /**
     * @return array<string, StepResult>
     */
    public function getStepResults(): array
    {
        return $this->stepResults;
    }

    public function resolveValue(string $path): mixed
    {
        if ('' === $path) {
            return null;
        }

        $parts = explode('.', $path);
        $root = array_shift($parts);

        return match ($root) {
            'input' => $this->resolveFromArray($this->input, $parts),
            'steps' => $this->resolveFromSteps($parts),
            'variables' => $this->resolveFromArray($this->variables, $parts),
            default => null,
        };
    }

    /**
     * @param list<string> $parts
     */
    private function resolveFromSteps(array $parts): mixed
    {
        if (count($parts) < 2) {
            return null;
        }

        $stepId = array_shift($parts);
        $result = $this->stepResults[$stepId] ?? null;
        if (null === $result) {
            return null;
        }

        $section = array_shift($parts);

        return match ($section) {
            'output' => $this->resolveFromArray($result->output, $parts),
            'status' => $result->status->value,
            'message' => $result->message,
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string>         $parts
     */
    private function resolveFromArray(array $data, array $parts): mixed
    {
        $current = $data;
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }

        return $current;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $results = [];
        foreach ($this->stepResults as $stepId => $result) {
            $results[$stepId] = $result->toArray();
        }

        return [
            'input' => $this->input,
            'variables' => $this->variables,
            'stepResults' => $results,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $stepResults = [];
        foreach ($data['stepResults'] ?? [] as $stepId => $resultData) {
            $stepResults[$stepId] = new StepResult(
                status: StepResultStatus::from($resultData['status']),
                output: $resultData['output'] ?? [],
                message: $resultData['message'] ?? '',
            );
        }

        return new self(
            input: $data['input'] ?? [],
            variables: $data['variables'] ?? [],
            stepResults: $stepResults,
        );
    }
}
