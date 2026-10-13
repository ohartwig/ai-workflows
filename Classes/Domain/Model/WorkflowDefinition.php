<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Model;

final readonly class WorkflowDefinition
{
    /**
     * @param InputDefinition[]    $inputs
     * @param StepDefinition[]     $steps
     * @param array<string, mixed> $notifications
     */
    public function __construct(
        public string $name,
        public string $description,
        public WorkflowTrigger $trigger,
        public array $inputs,
        public array $steps,
        public array $notifications = [],
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $inputs = [];
        foreach ($data['inputs'] ?? [] as $name => $inputData) {
            $inputs[] = InputDefinition::fromArray((string) $name, is_array($inputData) ? $inputData : []);
        }

        $steps = [];
        foreach ($data['steps'] ?? [] as $id => $stepData) {
            $steps[] = StepDefinition::fromArray((string) $id, $stepData);
        }

        return new self(
            name: $data['name'] ?? '',
            description: $data['description'] ?? '',
            trigger: WorkflowTrigger::fromArray($data['trigger'] ?? []),
            inputs: $inputs,
            steps: $steps,
            notifications: $data['notifications'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $steps = [];
        foreach ($this->steps as $step) {
            $steps[$step->id] = $step->toArray();
        }

        $inputs = [];
        foreach ($this->inputs as $input) {
            $inputs[$input->name] = $input->toArray();
        }

        return [
            'name' => $this->name,
            'description' => $this->description,
            'trigger' => $this->trigger->toArray(),
            'inputs' => $inputs,
            'steps' => $steps,
            'notifications' => $this->notifications,
        ];
    }
}
