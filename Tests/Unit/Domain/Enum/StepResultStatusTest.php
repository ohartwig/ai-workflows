<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Domain\Enum;

use Koh\AiWorkflows\Domain\Enum\StepResultStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StepResultStatusTest extends TestCase
{
    #[Test]
    public function hasAllExpectedCases(): void
    {
        self::assertCount(4, StepResultStatus::cases());
        self::assertSame('completed', StepResultStatus::Completed->value);
        self::assertSame('failed', StepResultStatus::Failed->value);
        self::assertSame('skipped', StepResultStatus::Skipped->value);
        self::assertSame('blocked', StepResultStatus::Blocked->value);
    }
}
