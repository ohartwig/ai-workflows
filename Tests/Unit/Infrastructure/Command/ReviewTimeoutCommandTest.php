<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Command;

use Koh\AiWorkflows\Application\Service\ExpressionResolver;
use Koh\AiWorkflows\Application\Service\StepExecutor;
use Koh\AiWorkflows\Application\Service\WorkflowEngine;
use Koh\AiWorkflows\Domain\Contract\ReviewTaskRepositoryInterface;
use Koh\AiWorkflows\Domain\Contract\WorkflowInstanceRepositoryInterface;
use Koh\AiWorkflows\Domain\Enum\ReviewStatus;
use Koh\AiWorkflows\Infrastructure\Command\ReviewTimeoutCommand;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\ReviewTask;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\WorkflowInstance;
use Koh\AiWorkflows\Infrastructure\Persistence\WorkflowInstanceFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * This command runs unattended on a schedule and is the only thing that frees a
 * workflow nobody reviewed. Two properties matter.
 *
 * It has to mark the task *and* cancel the workflow — doing only the first
 * leaves a workflow running forever against a review that is already closed.
 *
 * And one task whose workflow cannot be cancelled must not abort the run, or a
 * single broken record blocks every later task on every future run.
 */
final class ReviewTimeoutCommandTest extends TestCase
{
    private ReviewTaskRepositoryInterface&MockObject $reviewTasks;
    private WorkflowInstanceRepositoryInterface&MockObject $instances;
    private PersistenceManagerInterface&MockObject $persistence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reviewTasks = $this->createMock(ReviewTaskRepositoryInterface::class);
        $this->instances = $this->createMock(WorkflowInstanceRepositoryInterface::class);
        $this->persistence = $this->createMock(PersistenceManagerInterface::class);
    }

    private function task(int $uid, string $title, int $workflowUid): ReviewTask
    {
        $task = new ReviewTask();
        $task->_setProperty('uid', $uid);
        $task->setTitle($title);
        $task->setWorkflowInstance($workflowUid);
        $task->setStatus(ReviewStatus::Pending->value);

        return $task;
    }

    /**
     * WorkflowEngine is final, so a real one is built. Its cancel() resolves the
     * instance through the repository — which is the seam used below to make a
     * cancellation succeed or fail.
     */
    private function tester(): CommandTester
    {
        $engine = new WorkflowEngine(
            new StepExecutor([], new ExpressionResolver()),
            $this->instances,
            $this->createMock(EventDispatcherInterface::class),
            new WorkflowInstanceFactory(),
        );

        return new CommandTester(
            new ReviewTimeoutCommand($this->reviewTasks, $engine, $this->persistence),
        );
    }

    #[Test]
    public function anEmptyQueueIsReportedAndChangesNothing(): void
    {
        $this->reviewTasks->method('findOverdue')->willReturn([]);
        $this->reviewTasks->expects(self::never())->method('update');
        $this->persistence->expects(self::never())->method('persistAll');

        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('No overdue review tasks found', $tester->getDisplay());
    }

    /**
     * The two halves that have to happen together: the task is closed as timed
     * out, and the workflow it was blocking is cancelled.
     */
    #[Test]
    public function anOverdueTaskIsTimedOutAndItsWorkflowCancelled(): void
    {
        $task = $this->task(88, 'Approve the intro', 17);
        $instance = new WorkflowInstance();

        $this->reviewTasks->method('findOverdue')->willReturn([$task]);
        $this->instances->method('findByUid')->willReturn($instance);
        $this->instances->expects(self::once())->method('update')->with($instance);
        $this->reviewTasks->expects(self::once())->method('update')->with($task);
        $this->persistence->expects(self::once())->method('persistAll');

        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute([]));

        self::assertSame(ReviewStatus::Timeout->value, $task->getStatus());
        self::assertGreaterThan(0, $task->getReviewedAt(), 'the decision time has to be recorded');
        self::assertSame('Automatically timed out', $task->getReviewComment());

        $display = $tester->getDisplay();
        self::assertStringContainsString('#88', $display);
        self::assertStringContainsString('Approve the intro', $display);
        self::assertStringContainsString('#17', $display);
    }

    /**
     * The regression worth guarding. A task pointing at a workflow that no longer
     * exists throws inside cancel(); if that escaped, the run would stop and
     * every later overdue task would stay open — forever, since the next run
     * would hit the same record first.
     */
    #[Test]
    public function aWorkflowThatCannotBeCancelledDoesNotStopTheRun(): void
    {
        $broken = $this->task(88, 'Points at a deleted workflow', 999);
        $healthy = $this->task(89, 'Approve the outro', 17);

        $this->reviewTasks->method('findOverdue')->willReturn([$broken, $healthy]);
        $this->instances->method('findByUid')->willReturnCallback(
            static fn(int $uid): ?WorkflowInstance => 999 === $uid ? null : new WorkflowInstance(),
        );
        $this->reviewTasks->expects(self::exactly(2))->method('update');
        $this->persistence->expects(self::once())->method('persistAll');

        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute([]));

        $display = $tester->getDisplay();
        self::assertStringContainsString('could not cancel workflow #999', $display);
        self::assertStringContainsString('Processed 2 overdue review task(s)', $display);

        self::assertSame(
            ReviewStatus::Timeout->value,
            $broken->getStatus(),
            'the task is still closed even when its workflow is gone — otherwise it stays overdue forever',
        );
        self::assertSame(ReviewStatus::Timeout->value, $healthy->getStatus());
    }

    #[Test]
    public function everyOverdueTaskIsCounted(): void
    {
        $this->reviewTasks->method('findOverdue')->willReturn([
            $this->task(1, 'a', 10),
            $this->task(2, 'b', 11),
            $this->task(3, 'c', 12),
        ]);
        $this->instances->method('findByUid')->willReturn(new WorkflowInstance());

        $tester = $this->tester();
        $tester->execute([]);

        self::assertStringContainsString('Found 3 overdue review task(s)', $tester->getDisplay());
        self::assertStringContainsString('Processed 3 overdue review task(s)', $tester->getDisplay());
    }

    /**
     * Overdue is relative to now, and the command is what supplies that. Passing
     * a fixed or zero timestamp would select nothing on every run.
     */
    #[Test]
    public function tasksAreSelectedAgainstTheCurrentTime(): void
    {
        $asked = null;
        $this->reviewTasks->method('findOverdue')->willReturnCallback(
            static function (int $timestamp) use (&$asked): array {
                $asked = $timestamp;

                return [];
            },
        );

        $this->tester()->execute([]);

        self::assertNotNull($asked);
        self::assertEqualsWithDelta(time(), $asked, 5);
    }
}
