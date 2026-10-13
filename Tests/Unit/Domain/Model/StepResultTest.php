<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Domain\Model;

use Koh\AiWorkflows\Domain\Enum\StepResultStatus;
use Koh\AiWorkflows\Domain\Model\StepResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StepResultTest extends TestCase
{
    #[Test]
    public function successCreatesCompletedResult(): void
    {
        $result = StepResult::success(['key' => 'value'], 'Done');
        self::assertSame(StepResultStatus::Completed, $result->status);
        self::assertSame(['key' => 'value'], $result->output);
        self::assertSame('Done', $result->message);
        self::assertTrue($result->isSuccessful());
    }

    #[Test]
    public function errorCreatesFailedResult(): void
    {
        $result = StepResult::error('Something went wrong', ['debug' => 'info']);
        self::assertSame(StepResultStatus::Failed, $result->status);
        self::assertSame('Something went wrong', $result->message);
        self::assertFalse($result->isSuccessful());
    }

    #[Test]
    public function blockedCreatesBlockedResult(): void
    {
        $result = StepResult::blocked('Waiting');
        self::assertSame(StepResultStatus::Blocked, $result->status);
        self::assertSame([], $result->output);
        self::assertFalse($result->isSuccessful());
    }

    #[Test]
    public function skippedCreatesSkippedResult(): void
    {
        $result = StepResult::skipped('Not needed');
        self::assertSame(StepResultStatus::Skipped, $result->status);
        self::assertFalse($result->isSuccessful());
    }

    #[Test]
    public function toArrayReturnsExpectedStructure(): void
    {
        $result = StepResult::success(['content' => 'test'], 'OK');
        $array = $result->toArray();
        self::assertSame('completed', $array['status']);
        self::assertSame(['content' => 'test'], $array['output']);
        self::assertSame('OK', $array['message']);
    }

    #[Test]
    public function successWithDefaults(): void
    {
        $result = StepResult::success();
        self::assertSame([], $result->output);
        self::assertSame('', $result->message);
        self::assertTrue($result->isSuccessful());
    }
}
