<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Command;

use Koh\AiWorkflows\Application\Service\ExpressionResolver;
use Koh\AiWorkflows\Application\Service\StepExecutor;
use Koh\AiWorkflows\Application\Service\WorkflowEngine;
use Koh\AiWorkflows\Domain\Contract\StepInterface;
use Koh\AiWorkflows\Domain\Contract\WorkflowDiscoveryInterface;
use Koh\AiWorkflows\Domain\Contract\WorkflowInstanceRepositoryInterface;
use Koh\AiWorkflows\Domain\Enum\TriggerType;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowTrigger;
use Koh\AiWorkflows\Infrastructure\Command\WorkflowRunCommand;
use Koh\AiWorkflows\Infrastructure\Persistence\WorkflowInstanceFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The manual entry point into the workflow engine, and the one a scheduler or an
 * operator reads the exit code of.
 *
 * The distinction that matters is between *paused* and *failed*. A workflow that
 * stops for human review has not gone wrong — reporting it as a failure would
 * make a scheduled run look broken every time a review is requested, and would
 * train whoever watches it to ignore the red.
 */
final class WorkflowRunCommandTest extends TestCase
{
    private WorkflowDiscoveryInterface&MockObject $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discovery = $this->createMock(WorkflowDiscoveryInterface::class);
    }

    private function step(string $type, StepResult $result): StepInterface&MockObject
    {
        $step = $this->createMock(StepInterface::class);
        $step->method('getType')->willReturn($type);
        $step->method('validate')->willReturn([]);
        $step->method('execute')->willReturn($result);

        return $step;
    }

    /**
     * @param list<StepInterface> $steps
     */
    private function tester(array $steps = []): CommandTester
    {
        $engine = new WorkflowEngine(
            new StepExecutor($steps, new ExpressionResolver()),
            $this->createMock(WorkflowInstanceRepositoryInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            new WorkflowInstanceFactory(),
        );

        return new CommandTester(new WorkflowRunCommand($this->discovery, $engine));
    }

    private function definition(string $stepType = 'test'): WorkflowDefinition
    {
        return new WorkflowDefinition(
            'Content Pipeline',
            'Generate and review',
            new WorkflowTrigger(TriggerType::Manual),
            [],
            [new StepDefinition('s1', $stepType)],
        );
    }

    #[Test]
    public function anUnknownWorkflowIsAFailure(): void
    {
        $this->discovery->method('getWorkflow')->willReturn(null);

        $tester = $this->tester();

        self::assertSame(Command::FAILURE, $tester->execute(['name' => 'nope']));
        self::assertStringContainsString('not found', $tester->getDisplay());
    }

    /**
     * The input arrives as a JSON string on the command line. Malformed JSON has
     * to be named as such rather than reaching the engine as an empty run.
     */
    #[Test]
    public function malformedInputJsonIsRejectedBeforeAnythingRuns(): void
    {
        $this->discovery->method('getWorkflow')->willReturn($this->definition());

        $step = $this->step('test', StepResult::success());
        $step->expects(self::never())->method('execute');

        $tester = $this->tester([$step]);

        self::assertSame(Command::FAILURE, $tester->execute(['name' => 'pipeline', '--input' => '{not json']));
        self::assertStringContainsString('Invalid JSON input', $tester->getDisplay());
    }

    #[Test]
    public function aCompletedWorkflowSucceeds(): void
    {
        $this->discovery->method('getWorkflow')->willReturn($this->definition());

        $tester = $this->tester([$this->step('test', StepResult::success())]);

        self::assertSame(Command::SUCCESS, $tester->execute(['name' => 'pipeline']));
        self::assertStringContainsString('completed successfully', $tester->getDisplay());
    }

    /**
     * The regression worth guarding: a workflow waiting for a reviewer is a
     * normal outcome and must not be reported as a failed run.
     */
    #[Test]
    public function aWorkflowPausedForReviewIsNotAFailure(): void
    {
        $this->discovery->method('getWorkflow')->willReturn($this->definition('blocking'));

        $tester = $this->tester([$this->step('blocking', StepResult::blocked('Waiting for human review'))]);

        self::assertSame(Command::SUCCESS, $tester->execute(['name' => 'pipeline']));

        $display = $tester->getDisplay();
        self::assertStringContainsString('paused', $display);
        self::assertStringContainsString('awaiting human review', $display);
    }

    #[Test]
    public function aFailedWorkflowReportsFailure(): void
    {
        $this->discovery->method('getWorkflow')->willReturn($this->definition('failing'));

        $tester = $this->tester([$this->step('failing', StepResult::error('the model returned nothing'))]);

        self::assertSame(Command::FAILURE, $tester->execute(['name' => 'pipeline']));

        $display = $tester->getDisplay();
        self::assertStringContainsString('failed at step', $display);
        self::assertStringContainsString('the model returned nothing', $display);
    }

    /**
     * An engine that throws rather than returning a failed instance still has to
     * produce an exit code, not a stack trace on the scheduler's console.
     */
    #[Test]
    public function anEngineCrashIsReportedAsAFailure(): void
    {
        $this->discovery->method('getWorkflow')->willReturn($this->definition('exploding'));

        $step = $this->createMock(StepInterface::class);
        $step->method('getType')->willReturn('exploding');
        $step->method('validate')->willReturn([]);
        $step->method('execute')->willThrowException(new \RuntimeException('connection lost'));

        $tester = $this->tester([$step]);

        // The engine converts a step exception into a failed instance, so this
        // still exits FAILURE — via the status branch rather than the catch.
        self::assertSame(Command::FAILURE, $tester->execute(['name' => 'pipeline']));
        self::assertStringContainsString('connection lost', $tester->getDisplay());
    }

    #[Test]
    public function theWorkflowIsLookedUpByNameAndDirectory(): void
    {
        $asked = [];
        $this->discovery->method('getWorkflow')->willReturnCallback(
            function (string $directory, string $name) use (&$asked): ?WorkflowDefinition {
                $asked = [$directory, $name];

                return $this->definition();
            },
        );

        $this->tester([$this->step('test', StepResult::success())])
            ->execute(['name' => 'pipeline', '--directory' => 'EXT:custom/Workflows/']);

        self::assertSame(['EXT:custom/Workflows/', 'pipeline'], $asked);
    }

    /**
     * Without an explicit directory the command has to fall back to the
     * extension's own workflow folder, or every scheduled call needs the flag.
     */
    #[Test]
    public function theExtensionsOwnWorkflowDirectoryIsTheDefault(): void
    {
        $directory = null;
        $this->discovery->method('getWorkflow')->willReturnCallback(
            function (string $dir) use (&$directory): ?WorkflowDefinition {
                $directory = $dir;

                return $this->definition();
            },
        );

        $this->tester([$this->step('test', StepResult::success())])->execute(['name' => 'pipeline']);

        self::assertSame('EXT:ai_workflows/Resources/Private/Workflows/', $directory);
    }

    #[Test]
    public function theWorkflowDetailsAreShownBeforeRunning(): void
    {
        $this->discovery->method('getWorkflow')->willReturn($this->definition());

        $tester = $this->tester([$this->step('test', StepResult::success())]);
        $tester->execute(['name' => 'pipeline']);

        $display = $tester->getDisplay();
        self::assertStringContainsString('Content Pipeline', $display);
        self::assertStringContainsString('Generate and review', $display);
        self::assertStringContainsString('manual', $display);
    }
}
