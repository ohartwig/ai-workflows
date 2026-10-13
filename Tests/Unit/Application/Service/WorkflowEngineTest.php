<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Application\Service;

use Koh\AiWorkflows\Application\Exception\WorkflowExecutionException;
use Koh\AiWorkflows\Application\Service\ExpressionResolver;
use Koh\AiWorkflows\Application\Service\StepExecutor;
use Koh\AiWorkflows\Application\Service\WorkflowEngine;
use Koh\AiWorkflows\Domain\Contract\StepInterface;
use Koh\AiWorkflows\Domain\Contract\WorkflowInstanceRepositoryInterface;
use Koh\AiWorkflows\Domain\Enum\WorkflowStatus;
use Koh\AiWorkflows\Domain\Enum\TriggerType;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowTrigger;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\WorkflowInstance;
use Koh\AiWorkflows\Infrastructure\Persistence\WorkflowInstanceFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WorkflowEngineTest extends TestCase
{
    private WorkflowInstanceRepositoryInterface $repository;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WorkflowInstanceRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    private function createEngine(array $steps = []): WorkflowEngine
    {
        return new WorkflowEngine(
            new StepExecutor($steps, new ExpressionResolver()),
            $this->repository,
            $this->eventDispatcher,
            new WorkflowInstanceFactory(),
        );
    }

    private function makeDef(array $steps = []): WorkflowDefinition
    {
        $defs = [];
        foreach ($steps as $id => $type) {
            $defs[] = new StepDefinition($id, $type);
        }

        return new WorkflowDefinition('Test', '', new WorkflowTrigger(TriggerType::Manual), [], $defs);
    }

    #[Test]
    public function startExecutesAllStepsAndCompletes(): void
    {
        $step = $this->createMock(StepInterface::class);
        $step->method('getType')->willReturn('test');
        $step->method('validate')->willReturn([]);
        $step->method('execute')->willReturn(StepResult::success(['done' => true]));

        $instance = $this->createEngine([$step])->start($this->makeDef(['s1' => 'test', 's2' => 'test']), ['topic' => 'x']);
        self::assertSame(WorkflowStatus::Completed->value, $instance->getStatus());
    }

    #[Test]
    public function startPausesOnBlockingStep(): void
    {
        $normal = $this->createMock(StepInterface::class);
        $normal->method('getType')->willReturn('normal');
        $normal->method('validate')->willReturn([]);
        $normal->method('execute')->willReturn(StepResult::success());

        $blocking = $this->createMock(StepInterface::class);
        $blocking->method('getType')->willReturn('block');
        $blocking->method('validate')->willReturn([]);
        $blocking->method('execute')->willReturn(StepResult::blocked('Waiting'));

        $def = new WorkflowDefinition('T', '', new WorkflowTrigger(TriggerType::Manual), [], [
            new StepDefinition('s1', 'normal'), new StepDefinition('s2', 'block'), new StepDefinition('s3', 'normal'),
        ]);
        $instance = $this->createEngine([$normal, $blocking])->start($def);
        self::assertSame(WorkflowStatus::Paused->value, $instance->getStatus());
    }

    #[Test]
    public function startFailsOnStepError(): void
    {
        $step = $this->createMock(StepInterface::class);
        $step->method('getType')->willReturn('fail');
        $step->method('validate')->willReturn([]);
        $step->method('execute')->willReturn(StepResult::error('Broke'));

        $instance = $this->createEngine([$step])->start($this->makeDef(['s1' => 'fail']));
        self::assertSame(WorkflowStatus::Failed->value, $instance->getStatus());
        self::assertSame('Broke', $instance->getErrorMessage());
    }

    #[Test]
    public function startFailsOnStepException(): void
    {
        $step = $this->createMock(StepInterface::class);
        $step->method('getType')->willReturn('throw');
        $step->method('validate')->willReturn([]);
        $step->method('execute')->willThrowException(new \RuntimeException('Unexpected'));

        $instance = $this->createEngine([$step])->start($this->makeDef(['s1' => 'throw']));
        self::assertSame(WorkflowStatus::Failed->value, $instance->getStatus());
        self::assertSame('Unexpected', $instance->getErrorMessage());
    }

    #[Test]
    public function cancelSetsStatusToCancelled(): void
    {
        $instance = new WorkflowInstance();
        $instance->setStatus(WorkflowStatus::Running->value);
        $this->repository->method('findByUid')->willReturn($instance);

        $this->createEngine()->cancel(1);
        self::assertSame(WorkflowStatus::Cancelled->value, $instance->getStatus());
    }

    #[Test]
    public function cancelThrowsForNonexistentInstance(): void
    {
        $this->repository->method('findByUid')->willReturn(null);
        $this->expectException(WorkflowExecutionException::class);
        $this->createEngine()->cancel(999);
    }

    #[Test]
    public function eventsAreDispatched(): void
    {
        $step = $this->createMock(StepInterface::class);
        $step->method('getType')->willReturn('test');
        $step->method('validate')->willReturn([]);
        $step->method('execute')->willReturn(StepResult::success());

        $this->eventDispatcher->expects(self::exactly(3))->method('dispatch');
        $this->createEngine([$step])->start($this->makeDef(['s1' => 'test']));
    }
}
