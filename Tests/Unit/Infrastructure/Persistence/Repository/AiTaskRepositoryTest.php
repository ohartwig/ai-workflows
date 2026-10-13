<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Persistence\Repository;

use Koh\AiWorkflows\Infrastructure\Persistence\Model\AiTask;
use Koh\AiWorkflows\Infrastructure\Persistence\Repository\AiTaskRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * An AI task is one step of a workflow handed to a model. The lookup by instance
 * and step is what keeps a paused workflow from generating its step twice, so the
 * pair of constraints has to stay a pair.
 */
final class AiTaskRepositoryTest extends TestCase
{
    use ExtbaseRepositoryDoubles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetQueryRecording();
    }

    /**
     * @param list<AiTask> $rows
     */
    private function repository(array $rows = []): AiTaskRepository
    {
        $repository = new AiTaskRepository();
        $repository->injectPersistenceManager($this->persistenceManagerFor($this->queryReturning($rows)));

        return $repository;
    }

    private function task(int $uid): AiTask
    {
        $task = new AiTask();
        $task->_setProperty('uid', $uid);

        return $task;
    }

    #[Test]
    public function lookupsAreNotLimitedToTheStoragePage(): void
    {
        $this->repository()->findPending();

        self::assertFalse($this->storagePageRespected);
    }

    #[Test]
    public function itQueriesItsOwnEntityType(): void
    {
        $this->repository()->findPending();

        self::assertSame(AiTask::class, $this->queriedType);
    }

    #[Test]
    public function aTaskIsFoundByUid(): void
    {
        self::assertSame(7, $this->repository([$this->task(7)])->findByUid(7)?->getUid());
        self::assertSame([['equals', 'uid', 7]], $this->recordedConstraints());
    }

    #[Test]
    public function anUnknownUidYieldsNull(): void
    {
        self::assertNull($this->repository()->findByUid(999));
    }

    /**
     * Only pending tasks may be picked up — handing an already completed task to
     * the model again would bill a second generation and overwrite the result.
     */
    #[Test]
    public function onlyPendingTasksAreQueued(): void
    {
        $tasks = $this->repository([$this->task(1), $this->task(2)])->findPending();

        self::assertCount(2, $tasks);
        self::assertSame([['equals', 'status', 'pending']], $this->recordedConstraints());
    }

    #[Test]
    public function nothingPendingYieldsAnEmptyList(): void
    {
        self::assertSame([], $this->repository()->findPending());
    }

    /**
     * The regression worth guarding: this is the idempotency check before a step
     * generates. Dropping either half of the constraint would match a different
     * step of the same workflow, or the same step of a different workflow — and
     * the step would then be skipped as "already done".
     */
    #[Test]
    public function aStepIsIdentifiedByItsWorkflowAndItsStepIdTogether(): void
    {
        $found = $this->repository([$this->task(3)])->findByWorkflowInstanceAndStep(42, 'generate-intro');

        self::assertSame(3, $found?->getUid());
        self::assertSame([
            ['equals', 'workflowInstance', 42],
            ['equals', 'stepId', 'generate-intro'],
        ], $this->recordedConstraints());
        self::assertSame(1, $this->andGroupCount, 'both conditions must hold, not either one');
    }

    #[Test]
    public function anUnknownWorkflowStepYieldsNull(): void
    {
        self::assertNull($this->repository()->findByWorkflowInstanceAndStep(42, 'missing'));
    }
}
