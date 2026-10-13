<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Domain\Enum;

use Koh\AiWorkflows\Domain\Enum\ReviewStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReviewStatusTest extends TestCase
{
    #[Test]
    public function hasAllExpectedCases(): void
    {
        self::assertCount(4, ReviewStatus::cases());
        self::assertSame('pending', ReviewStatus::Pending->value);
        self::assertSame('approved', ReviewStatus::Approved->value);
        self::assertSame('rejected', ReviewStatus::Rejected->value);
        self::assertSame('timeout', ReviewStatus::Timeout->value);
    }
}
