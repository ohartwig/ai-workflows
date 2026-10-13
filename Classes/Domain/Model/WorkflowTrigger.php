<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Model;

use Koh\AiWorkflows\Domain\Enum\TriggerType;

final readonly class WorkflowTrigger
{
    public function __construct(
        public TriggerType $type,
        public string $schedule = '',
        public string $event = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: TriggerType::from($data['type'] ?? 'manual'),
            schedule: $data['schedule'] ?? '',
            event: $data['event'] ?? '',
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'schedule' => $this->schedule,
            'event' => $this->event,
        ];
    }
}
