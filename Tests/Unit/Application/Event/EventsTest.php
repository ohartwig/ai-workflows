<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Application\Event;

use Koh\AiWorkflows\Application\Event\ReviewRequestedEvent;
use Koh\AiWorkflows\Application\Event\StepCompletedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowCompletedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowFailedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowStartedEvent;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    #[Test]
    public function workflowStartedEvent(): void
    {
        $e = new WorkflowStartedEvent(1, 'test', ['k' => 'v'], 42);
        self::assertSame(1, $e->workflowInstanceUid);
        self::assertSame('test', $e->workflowName);
        self::assertSame(['k' => 'v'], $e->input);
        self::assertSame(42, $e->startedBy);
    }

    #[Test]
    public function stepCompletedEvent(): void
    {
        $r = StepResult::success(['out' => 'data']);
        $e = new StepCompletedEvent(1, 'step1', 'ai_generate', $r);
        self::assertSame(1, $e->workflowInstanceUid);
        self::assertSame('step1', $e->stepId);
        self::assertSame($r, $e->result);
    }

    #[Test]
    public function reviewRequestedEvent(): void
    {
        $e = new ReviewRequestedEvent(1, 5, 'Review This');
        self::assertSame(1, $e->workflowInstanceUid);
        self::assertSame(5, $e->reviewTaskUid);
        self::assertSame('Review This', $e->title);
    }

    #[Test]
    public function workflowCompletedEvent(): void
    {
        $ctx = new WorkflowContext(input: ['test' => true]);
        $e = new WorkflowCompletedEvent(1, 'wf', $ctx);
        self::assertSame(1, $e->workflowInstanceUid);
        self::assertSame('wf', $e->workflowName);
        self::assertSame($ctx, $e->finalContext);
    }

    #[Test]
    public function workflowFailedEvent(): void
    {
        $e = new WorkflowFailedEvent(1, 'step3', 'Broke');
        self::assertSame(1, $e->workflowInstanceUid);
        self::assertSame('step3', $e->failedStepId);
        self::assertSame('Broke', $e->errorMessage);
    }
}
