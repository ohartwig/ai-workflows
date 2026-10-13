<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Persistence\Repository;

use Koh\AiWorkflows\Infrastructure\Persistence\Model\ReviewTask;
use Koh\AiWorkflows\Infrastructure\Persistence\Repository\ReviewTaskRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A review task is a workflow waiting on a human. The lookups here decide which
 * ones an operator is shown and which ones the timeout command escalates, so a
 * constraint that is subtly wrong does not error — it quietly escalates the wrong
 * tasks, or none.
 */
final class ReviewTaskRepositoryTest extends TestCase
{
    use ExtbaseRepositoryDoubles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetQueryRecording();
    }

    /**
     * @param list<ReviewTask> $rows
     */
    private function repository(array $rows = []): ReviewTaskRepository
    {
        $repository = new ReviewTaskRepository();
        $repository->injectPersistenceManager($this->persistenceManagerFor($this->queryReturning($rows)));

        return $repository;
    }

    private function task(int $uid): ReviewTask
    {
        $task = new ReviewTask();
        $task->_setProperty('uid', $uid);

        return $task;
    }

    /**
     * Workflow records do not live on the plugin's storage page. With the
     * restriction left in place every lookup would return nothing at all.
     */
    #[Test]
    public function lookupsAreNotLimitedToTheStoragePage(): void
    {
        $this->repository()->findByStatus('pending');

        self::assertFalse($this->storagePageRespected);
    }

    #[Test]
    public function itQueriesItsOwnEntityType(): void
    {
        $this->repository()->findByStatus('pending');

        self::assertSame(ReviewTask::class, $this->queriedType);
    }

    #[Test]
    public function aTaskIsFoundByUid(): void
    {
        $found = $this->repository([$this->task(7)])->findByUid(7);

        self::assertSame(7, $found?->getUid());
        self::assertSame([['equals', 'uid', 7]], $this->recordedConstraints());
    }

    #[Test]
    public function anUnknownUidYieldsNull(): void
    {
        self::assertNull($this->repository()->findByUid(999));
    }

    #[Test]
    public function tasksAreFoundByStatus(): void
    {
        $tasks = $this->repository([$this->task(1), $this->task(2)])->findByStatus('approved');

        self::assertCount(2, $tasks);
        self::assertSame([['equals', 'status', 'approved']], $this->recordedConstraints());
    }

    #[Test]
    public function tasksAreFoundByWorkflowInstance(): void
    {
        $tasks = $this->repository([$this->task(1)])->findByWorkflowInstance(42);

        self::assertCount(1, $tasks);
        self::assertSame([['equals', 'workflowInstance', 42]], $this->recordedConstraints());
    }

    #[Test]
    public function noMatchesYieldAnEmptyList(): void
    {
        self::assertSame([], $this->repository()->findByStatus('rejected'));
        self::assertSame([], $this->repository()->findByWorkflowInstance(1));
        self::assertSame([], $this->repository()->findOverdue(1785300000));
    }

    /**
     * The regression worth guarding. "Overdue" is three conditions, and the third
     * is the one that is easy to forget: a deadline of 0 means *no* deadline was
     * set. Without `deadline > 0` every open task without a deadline would be
     * treated as overdue and escalated on the first run of the timeout command.
     */
    #[Test]
    public function overdueMeansPendingWithADeadlineThatHasPassedAndWasActuallySet(): void
    {
        $this->repository([$this->task(1)])->findOverdue(1785300000);

        self::assertSame([
            ['equals', 'status', 'pending'],
            ['lessThan', 'deadline', 1785300000],
            ['greaterThan', 'deadline', 0],
        ], $this->recordedConstraints());

        self::assertSame(1, $this->andGroupCount, 'the three conditions must be combined with AND, not OR');
    }

    /**
     * Only pending tasks can be overdue — an already approved one must never be
     * escalated a second time.
     */
    #[Test]
    public function overdueOnlyConsidersPendingTasks(): void
    {
        $this->repository()->findOverdue(1785300000);

        self::assertContains(['equals', 'status', 'pending'], $this->recordedConstraints());
    }
}
