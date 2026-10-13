<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Domain\Model;

use Koh\AiWorkflows\Domain\Enum\TriggerType;
use Koh\AiWorkflows\Domain\Model\WorkflowTrigger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WorkflowTriggerTest extends TestCase
{
    #[Test]
    public function fromArrayCreatesManualTrigger(): void
    {
        $trigger = WorkflowTrigger::fromArray(['type' => 'manual']);
        self::assertSame(TriggerType::Manual, $trigger->type);
        self::assertSame('', $trigger->schedule);
    }

    #[Test]
    public function fromArrayCreatesScheduleTrigger(): void
    {
        $trigger = WorkflowTrigger::fromArray(['type' => 'schedule', 'schedule' => '*/5 * * * *']);
        self::assertSame(TriggerType::Schedule, $trigger->type);
        self::assertSame('*/5 * * * *', $trigger->schedule);
    }

    #[Test]
    public function toArrayReturnsExpectedStructure(): void
    {
        $trigger = new WorkflowTrigger(TriggerType::Event, '', 'content.created');
        $array = $trigger->toArray();
        self::assertSame('event', $array['type']);
        self::assertSame('content.created', $array['event']);
    }

    #[Test]
    public function fromArrayDefaultsToManual(): void
    {
        $trigger = WorkflowTrigger::fromArray([]);
        self::assertSame(TriggerType::Manual, $trigger->type);
    }
}
