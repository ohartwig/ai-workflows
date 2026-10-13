<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Step;

use Koh\AiWorkflows\Application\Event\ReviewRequestedEvent;
use Koh\AiWorkflows\Domain\Contract\ReviewTaskRepositoryInterface;
use Koh\AiWorkflows\Domain\Enum\StepResultStatus;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\ReviewTask;
use Koh\AiWorkflows\Infrastructure\Step\HumanReviewStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * This step is the handover from machine to person: it stores a review task and
 * blocks the workflow until someone answers.
 *
 * Two things carry real consequence. The step must report *blocked* rather than
 * success — a success would let the engine run straight past the review it just
 * asked for. And the deadline has to be an absolute future timestamp, because
 * the timeout command selects on `deadline < now`; storing the raw offset would
 * make every task instantly overdue.
 */
final class HumanReviewStepTest extends TestCase
{
    /** @var list<ReviewTask> */
    private array $added = [];

    /** @var list<object> */
    private array $events = [];

    private HumanReviewStep $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->added = [];
        $this->events = [];

        $repository = $this->createMock(ReviewTaskRepositoryInterface::class);
        $repository->method('add')->willReturnCallback(
            function (object $task): void {
                self::assertInstanceOf(ReviewTask::class, $task);
                $this->added[] = $task;
            },
        );

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            function (object $event): object {
                $this->events[] = $event;

                return $event;
            },
        );

        $this->step = new HumanReviewStep($repository, $dispatcher);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function definition(array $parameters = []): StepDefinition
    {
        return new StepDefinition('review-intro', 'human_review', $parameters);
    }

    private function context(int $workflowInstanceUid = 17): WorkflowContext
    {
        return new WorkflowContext()->withVariable('workflowInstanceUid', $workflowInstanceUid);
    }

    private function stored(): ReviewTask
    {
        self::assertCount(1, $this->added, 'exactly one review task must have been stored');

        return $this->added[0];
    }

    #[Test]
    public function itDeclaresItselfAsABlockingReviewStep(): void
    {
        self::assertSame('human_review', $this->step->getType());
        self::assertTrue($this->step->isBlocking(), 'the workflow has to wait for the person');
    }

    /**
     * The regression that matters most: a success result here would let the
     * engine continue past a review nobody has answered.
     */
    #[Test]
    public function theWorkflowIsLeftBlocked(): void
    {
        $result = $this->step->execute($this->definition(['title' => 'Approve the intro']), $this->context());

        self::assertSame(StepResultStatus::Blocked, $result->status);
        self::assertFalse($result->isSuccessful(), 'a blocked step must not read as done');
    }

    #[Test]
    public function theReviewTaskCarriesItsStepAndWorkflow(): void
    {
        $this->step->execute($this->definition(['title' => 'Approve the intro', 'description' => 'Check the tone']), $this->context(17));

        $task = $this->stored();
        self::assertSame(17, $task->getWorkflowInstance());
        self::assertSame('review-intro', $task->getStepId());
        self::assertSame('Approve the intro', $task->getTitle());
        self::assertSame('Check the tone', $task->getDescription());
        self::assertSame('pending', $task->getStatus());
    }

    /**
     * A workflow without a title still has to produce something an operator can
     * recognise in the review list.
     */
    #[Test]
    public function anUntitledReviewGetsAReadableDefault(): void
    {
        $this->step->execute($this->definition(), $this->context());

        self::assertSame('Review Required', $this->stored()->getTitle());
        self::assertSame('', $this->stored()->getDescription());
    }

    /**
     * The deadline is compared against the clock by the timeout command, so it
     * has to be an absolute point in the future — not the offset itself.
     */
    #[Test]
    public function theDeadlineIsStoredAsAnAbsoluteFutureTimestamp(): void
    {
        $this->step->execute($this->definition(['deadline' => 3600]), $this->context());

        self::assertEqualsWithDelta(time() + 3600, $this->stored()->getDeadline(), 5);
    }

    #[Test]
    public function theDefaultDeadlineIsOneDay(): void
    {
        $this->step->execute($this->definition(), $this->context());

        self::assertEqualsWithDelta(time() + 86400, $this->stored()->getDeadline(), 5);
    }

    /**
     * Content reaches the step either as ready-made text or as a structure from
     * a previous step. The second must be encoded rather than cast, which would
     * store the word "Array".
     */
    #[Test]
    public function structuredContentIsStoredAsJsonWhileTextIsKept(): void
    {
        $this->step->execute($this->definition(['content' => 'a plain draft']), $this->context());
        self::assertSame('a plain draft', $this->stored()->getContentData());

        $this->added = [];
        $this->step->execute($this->definition(['content' => ['headline' => 'Hello']]), $this->context());
        self::assertSame('{"headline":"Hello"}', $this->stored()->getContentData());
    }

    /**
     * Without the event nothing tells the operator a review is waiting — the
     * task would sit in the table until someone happened to look.
     */
    #[Test]
    public function aReviewRequestedEventIsRaised(): void
    {
        $this->step->execute($this->definition(['title' => 'Approve the intro']), $this->context(17));

        self::assertCount(1, $this->events);
        $event = $this->events[0];
        self::assertInstanceOf(ReviewRequestedEvent::class, $event);
        self::assertSame(17, $event->workflowInstanceUid);
        self::assertSame('Approve the intro', $event->title);
    }

    #[Test]
    public function aReviewStepNeedsNoParametersToBeValid(): void
    {
        self::assertSame([], $this->step->validate($this->definition()));
    }
}
