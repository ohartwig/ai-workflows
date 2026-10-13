<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Domain\Enum;

use Koh\AiWorkflows\Domain\Enum\TriggerType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TriggerTypeTest extends TestCase
{
    #[Test]
    public function hasExpectedCases(): void
    {
        self::assertSame('manual', TriggerType::Manual->value);
        self::assertSame('schedule', TriggerType::Schedule->value);
        self::assertSame('event', TriggerType::Event->value);
    }

    #[Test]
    public function canBeCreatedFromString(): void
    {
        self::assertSame(TriggerType::Manual, TriggerType::from('manual'));
        self::assertSame(TriggerType::Schedule, TriggerType::from('schedule'));
    }

    #[Test]
    public function throwsForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        TriggerType::from('invalid');
    }
}
