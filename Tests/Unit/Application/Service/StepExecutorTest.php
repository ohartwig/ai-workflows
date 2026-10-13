<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Application\Service;

use Koh\AiWorkflows\Application\Exception\WorkflowExecutionException;
use Koh\AiWorkflows\Application\Service\ExpressionResolver;
use Koh\AiWorkflows\Application\Service\StepExecutor;
use Koh\AiWorkflows\Domain\Contract\StepInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StepExecutorTest extends TestCase
{
    #[Test]
    public function executesMatchingStep(): void
    {
        $step = $this->createMock(StepInterface::class);
        $step->method('getType')->willReturn('test_step');
        $step->method('validate')->willReturn([]);
        $step->method('execute')->willReturn(StepResult::success(['result' => 'ok']));

        $executor = new StepExecutor([$step], new ExpressionResolver());
        $result = $executor->execute(new StepDefinition('s1', 'test_step', ['p' => 'v']), new WorkflowContext());
        self::assertTrue($result->isSuccessful());
        self::assertSame('ok', $result->output['result']);
    }

    #[Test]
    public function throwsForUnknownStepType(): void
    {
        $executor = new StepExecutor([], new ExpressionResolver());
        $this->expectException(WorkflowExecutionException::class);
        $executor->execute(new StepDefinition('s1', 'unknown_type'), new WorkflowContext());
    }

    #[Test]
    public function throwsOnValidationFailure(): void
    {
        $step = $this->createMock(StepInterface::class);
        $step->method('getType')->willReturn('bad');
        $step->method('validate')->willReturn(['Missing "url"']);

        $executor = new StepExecutor([$step], new ExpressionResolver());
        $this->expectException(WorkflowExecutionException::class);
        $executor->execute(new StepDefinition('s1', 'bad'), new WorkflowContext());
    }

    #[Test]
    public function resolvesExpressionsInParameters(): void
    {
        $step = $this->createMock(StepInterface::class);
        $step->method('getType')->willReturn('test');
        $step->method('validate')->willReturn([]);
        $step->method('execute')->willReturnCallback(
            fn(StepDefinition $d, WorkflowContext $c) => StepResult::success(['resolved' => $d->parameters['prompt']]),
        );

        $executor = new StepExecutor([$step], new ExpressionResolver());
        $result = $executor->execute(
            new StepDefinition('s1', 'test', ['prompt' => '{{ input.topic }}']),
            new WorkflowContext(input: ['topic' => 'AI Safety']),
        );
        self::assertSame('AI Safety', $result->output['resolved']);
    }

    #[Test]
    public function hasStepAndIsBlocking(): void
    {
        $step = $this->createMock(StepInterface::class);
        $step->method('getType')->willReturn('human_review');
        $step->method('isBlocking')->willReturn(true);

        $executor = new StepExecutor([$step], new ExpressionResolver());
        self::assertTrue($executor->hasStep('human_review'));
        self::assertFalse($executor->hasStep('unknown'));
        self::assertTrue($executor->isBlocking('human_review'));
        self::assertFalse($executor->isBlocking('unknown'));
    }
}
