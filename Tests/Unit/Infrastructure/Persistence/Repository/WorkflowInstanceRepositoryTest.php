<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Persistence\Repository;

use Koh\AiWorkflows\Domain\Enum\WorkflowStatus;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\WorkflowInstance;
use Koh\AiWorkflows\Infrastructure\Persistence\Repository\WorkflowInstanceRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The engine resolves a running workflow through this repository before it may
 * cancel or resume it. A lookup that returns nothing is read as "no such
 * instance", so the storage-page restriction has to stay lifted.
 */
final class WorkflowInstanceRepositoryTest extends TestCase
{
    use ExtbaseRepositoryDoubles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetQueryRecording();
    }

    /**
     * @param list<WorkflowInstance> $rows
     */
    private function repository(array $rows = []): WorkflowInstanceRepository
    {
        $repository = new WorkflowInstanceRepository();
        $repository->injectPersistenceManager($this->persistenceManagerFor($this->queryReturning($rows)));

        return $repository;
    }

    private function instance(int $uid, string $status = 'running'): WorkflowInstance
    {
        $instance = new WorkflowInstance();
        $instance->_setProperty('uid', $uid);
        $instance->setStatus($status);

        return $instance;
    }

    #[Test]
    public function lookupsAreNotLimitedToTheStoragePage(): void
    {
        $this->repository()->findByUid(1);

        self::assertFalse($this->storagePageRespected);
    }

    #[Test]
    public function itQueriesItsOwnEntityType(): void
    {
        $this->repository()->findByUid(1);

        self::assertSame(WorkflowInstance::class, $this->queriedType);
    }

    #[Test]
    public function anInstanceIsFoundByUid(): void
    {
        $found = $this->repository([$this->instance(7)])->findByUid(7);

        self::assertSame(7, $found?->getUid());
        self::assertSame([['equals', 'uid', 7]], $this->recordedConstraints());
    }

    /**
     * The engine turns this null into "workflow does not exist" and refuses to
     * cancel, so it must genuinely mean not found.
     */
    #[Test]
    public function anUnknownUidYieldsNull(): void
    {
        self::assertNull($this->repository()->findByUid(999));
    }

    #[Test]
    public function instancesAreFoundByStatus(): void
    {
        $running = $this->repository([$this->instance(1), $this->instance(2)])
            ->findByStatus(WorkflowStatus::Running->value);

        self::assertCount(2, $running);
        self::assertSame(
            [['equals', 'status', WorkflowStatus::Running->value]],
            $this->recordedConstraints(),
        );
    }

    #[Test]
    public function noInstancesInThatStatusYieldAnEmptyList(): void
    {
        self::assertSame([], $this->repository()->findByStatus(WorkflowStatus::Paused->value));
    }
}
