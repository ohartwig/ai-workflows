<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Model;

final readonly class StepDefinition
{
    /**
     * @param array<string, mixed>  $parameters
     * @param array<string, string> $output
     * @param array<string, mixed>  $condition
     */
    public function __construct(
        public string $id,
        public string $type,
        public array $parameters = [],
        public array $output = [],
        public array $condition = [],
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $id, array $data): self
    {
        return new self(
            id: $id,
            type: $data['type'] ?? '',
            parameters: $data['parameters'] ?? [],
            output: $data['output'] ?? [],
            condition: $data['condition'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'parameters' => $this->parameters,
            'output' => $this->output,
            'condition' => $this->condition,
        ];
    }
}
