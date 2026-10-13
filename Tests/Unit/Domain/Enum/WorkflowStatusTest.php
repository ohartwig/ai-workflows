<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Domain\Enum;

use Koh\AiWorkflows\Domain\Enum\WorkflowStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WorkflowStatusTest extends TestCase
{
    #[Test]
    public function hasAllExpectedCases(): void
    {
        self::assertCount(6, WorkflowStatus::cases());
        self::assertSame('pending', WorkflowStatus::Pending->value);
        self::assertSame('running', WorkflowStatus::Running->value);
        self::assertSame('paused', WorkflowStatus::Paused->value);
        self::assertSame('completed', WorkflowStatus::Completed->value);
        self::assertSame('failed', WorkflowStatus::Failed->value);
        self::assertSame('cancelled', WorkflowStatus::Cancelled->value);
    }
}
