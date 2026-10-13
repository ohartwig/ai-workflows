<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Step;

use Koh\AiWorkflows\Application\Event\AiTaskCreatedEvent;
use Koh\AiWorkflows\Domain\Contract\AiTaskRepositoryInterface;
use Koh\AiWorkflows\Domain\Enum\StepResultStatus;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\AiTask;
use Koh\AiWorkflows\Infrastructure\Step\AiTaskStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * This step hands work to an agent and parks the workflow until an answer comes
 * back. The prompt is the whole instruction, so the interesting part is the
 * context assembly: `context_keys` maps names onto expressions that are resolved
 * against earlier step results. Resolving those wrongly sends the agent a prompt
 * about the wrong material — which costs a generation and produces plausible,
 * unusable output rather than an error.
 */
final class AiTaskStepTest extends TestCase
{
    /** @var list<AiTask> */
    private array $added = [];

    /** @var list<object> */
    private array $events = [];

    private AiTaskStep $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->added = [];
        $this->events = [];

        $repository = $this->createMock(AiTaskRepositoryInterface::class);
        $repository->method('add')->willReturnCallback(
            function (object $task): void {
                self::assertInstanceOf(AiTask::class, $task);
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

        $this->step = new AiTaskStep($repository, $dispatcher);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function definition(array $parameters = ['prompt' => 'Write an intro']): StepDefinition
    {
        return new StepDefinition('generate-intro', 'ai_task', $parameters);
    }

    private function context(int $workflowInstanceUid = 17): WorkflowContext
    {
        return new WorkflowContext()->withVariable('workflowInstanceUid', $workflowInstanceUid);
    }

    private function stored(): AiTask
    {
        self::assertCount(1, $this->added, 'exactly one task must have been stored');

        return $this->added[0];
    }

    #[Test]
    public function itDeclaresItselfAsABlockingAiStep(): void
    {
        self::assertSame('ai_task', $this->step->getType());
        self::assertTrue($this->step->isBlocking(), 'the workflow waits for the agent to answer');
    }

    #[Test]
    public function theWorkflowIsLeftBlocked(): void
    {
        $result = $this->step->execute($this->definition(), $this->context());

        self::assertSame(StepResultStatus::Blocked, $result->status);
        self::assertFalse($result->isSuccessful(), 'a blocked step must not read as done');
    }

    #[Test]
    public function theTaskCarriesItsPromptStepAndWorkflow(): void
    {
        $this->step->execute(
            $this->definition(['prompt' => 'Write an intro', 'task_type' => 'summarize']),
            $this->context(17),
        );

        $task = $this->stored();
        self::assertSame('Write an intro', $task->getPrompt());
        self::assertSame('summarize', $task->getTaskType());
        self::assertSame('generate-intro', $task->getStepId());
        self::assertSame(17, $task->getWorkflowInstance());
        self::assertSame('pending', $task->getStatus());
        self::assertEqualsWithDelta(time(), $task->getCreatedAt(), 5);
    }

    #[Test]
    public function theDefaultTaskTypeIsGeneration(): void
    {
        $this->step->execute($this->definition(), $this->context());

        self::assertSame('generate', $this->stored()->getTaskType());
    }

    /**
     * The part worth pinning: each context key is resolved against the workflow
     * context, so the agent receives the actual material rather than the
     * expression that points at it.
     */
    #[Test]
    public function contextKeysAreResolvedAgainstTheWorkflowContext(): void
    {
        $context = $this->context()->withVariable('topic', 'IPv6 migration');

        $this->step->execute(
            $this->definition([
                'prompt' => 'Write an intro',
                'context_keys' => ['subject' => 'variables.topic'],
            ]),
            $context,
        );

        /** @var array<string, mixed> $stored */
        $stored = \json_decode($this->stored()->getContextData(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(
            'IPv6 migration',
            $stored['subject'],
            'the agent must get the value, not the expression that points at it',
        );
    }

    #[Test]
    public function aTaskWithoutContextKeysStoresAnEmptyObject(): void
    {
        $this->step->execute($this->definition(), $this->context());

        self::assertSame('[]', $this->stored()->getContextData());
        self::assertSame('[]', $this->stored()->getExpectedSchema());
    }

    #[Test]
    public function anExpectedSchemaIsStoredAsJson(): void
    {
        $this->step->execute(
            $this->definition(['prompt' => 'Write an intro', 'expected_schema' => ['headline' => 'string']]),
            $this->context(),
        );

        self::assertSame('{"headline":"string"}', $this->stored()->getExpectedSchema());
    }

    #[Test]
    public function anAiTaskCreatedEventIsRaised(): void
    {
        $this->step->execute($this->definition(['prompt' => 'Write an intro', 'task_type' => 'summarize']), $this->context(17));

        self::assertCount(1, $this->events);
        $event = $this->events[0];
        self::assertInstanceOf(AiTaskCreatedEvent::class, $event);
        self::assertSame(17, $event->workflowInstanceUid);
        self::assertSame('summarize', $event->taskType);
        self::assertSame('Write an intro', $event->prompt);
    }

    /**
     * Validation runs before the workflow starts. Catching a missing prompt here
     * is the difference between a clear configuration error and a run that dies
     * at the step, halfway through.
     */
    #[Test]
    public function aMissingPromptIsReportedByValidation(): void
    {
        $errors = $this->step->validate($this->definition([]));

        self::assertCount(1, $errors);
        self::assertStringContainsString('prompt', $errors[0]);
    }

    #[Test]
    public function aPromptMakesTheStepValid(): void
    {
        self::assertSame([], $this->step->validate($this->definition()));
    }

    /**
     * If validation is skipped, execute() still must not store a task with an
     * empty prompt — the agent would be billed for an instruction-less call.
     */
    #[Test]
    public function executingWithoutAPromptFails(): void
    {
        $this->expectException(\Throwable::class);

        $this->step->execute($this->definition([]), $this->context());
    }
}
