<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Model;

use Koh\AiWorkflows\Domain\Enum\StepResultStatus;

final readonly class StepResult
{
    /**
     * @param array<string, mixed> $output
     */
    public function __construct(
        public StepResultStatus $status,
        public array $output = [],
        public string $message = '',
    ) {}

    /**
     * @param array<string, mixed> $output
     */
    public static function success(array $output = [], string $message = ''): self
    {
        return new self(StepResultStatus::Completed, $output, $message);
    }

    /**
     * @param array<string, mixed> $output
     */
    public static function error(string $message, array $output = []): self
    {
        return new self(StepResultStatus::Failed, $output, $message);
    }

    public static function blocked(string $message = ''): self
    {
        return new self(StepResultStatus::Blocked, [], $message);
    }

    public static function skipped(string $message = ''): self
    {
        return new self(StepResultStatus::Skipped, [], $message);
    }

    public function isSuccessful(): bool
    {
        return StepResultStatus::Completed === $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'output' => $this->output,
            'message' => $this->message,
        ];
    }
}
