<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Domain\Model;

use Koh\AiWorkflows\Domain\Enum\StepResultStatus;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WorkflowContextTest extends TestCase
{
    #[Test]
    public function constructorSetsInputAndVariables(): void
    {
        $context = new WorkflowContext(input: ['topic' => 'test'], variables: ['key' => 'value']);
        self::assertSame(['topic' => 'test'], $context->getInput());
        self::assertSame(['key' => 'value'], $context->getVariables());
    }

    #[Test]
    public function withVariableReturnsNewInstanceWithMergedValue(): void
    {
        $context = new WorkflowContext();
        $updated = $context->withVariable('foo', 'bar');
        self::assertNotSame($context, $updated);
        self::assertNull($context->getVariable('foo'), 'original context must not be mutated');
        self::assertSame('bar', $updated->getVariable('foo'));
        self::assertNull($updated->getVariable('missing'));
        self::assertSame('default', $updated->getVariable('missing', 'default'));
    }

    #[Test]
    public function withStepResultReturnsNewInstanceWithAddedResult(): void
    {
        $context = new WorkflowContext();
        $result = StepResult::success(['content' => 'hello']);
        $updated = $context->withStepResult('step1', $result);
        self::assertNotSame($context, $updated);
        self::assertNull($context->getStepResult('step1'), 'original context must not be mutated');
        self::assertSame($result, $updated->getStepResult('step1'));
        self::assertNull($updated->getStepResult('nonexistent'));
    }

    #[Test]
    public function resolveValueFromInput(): void
    {
        $context = new WorkflowContext(input: ['topic' => 'AI', 'nested' => ['key' => 'deep']]);
        self::assertSame('AI', $context->resolveValue('input.topic'));
        self::assertSame('deep', $context->resolveValue('input.nested.key'));
    }

    #[Test]
    public function resolveValueFromStepOutput(): void
    {
        $context = new WorkflowContext()
            ->withStepResult('gen', StepResult::success(['content' => 'Hello World']));
        self::assertSame('Hello World', $context->resolveValue('steps.gen.output.content'));
        self::assertSame('completed', $context->resolveValue('steps.gen.status'));
    }

    #[Test]
    public function resolveValueFromVariables(): void
    {
        $context = new WorkflowContext(variables: ['mode' => 'production']);
        self::assertSame('production', $context->resolveValue('variables.mode'));
    }

    #[Test]
    public function resolveValueReturnsNullForInvalidPaths(): void
    {
        $context = new WorkflowContext();
        self::assertNull($context->resolveValue(''));
        self::assertNull($context->resolveValue('unknown.path'));
        self::assertNull($context->resolveValue('steps.nonexistent.output.x'));
    }

    #[Test]
    public function toArrayAndFromArrayRoundTrip(): void
    {
        $context = new WorkflowContext(input: ['topic' => 'test'], variables: ['key' => 'value'])
            ->withStepResult('step1', StepResult::success(['out' => 'data'], 'ok'))
            ->withStepResult('step2', StepResult::error('failed'));

        $array = $context->toArray();
        $restored = WorkflowContext::fromArray($array);

        self::assertSame($context->getInput(), $restored->getInput());
        self::assertSame($context->getVariables(), $restored->getVariables());
        $r1 = $restored->getStepResult('step1');
        self::assertNotNull($r1);
        self::assertSame(StepResultStatus::Completed, $r1->status);
        $r2 = $restored->getStepResult('step2');
        self::assertNotNull($r2);
        self::assertSame(StepResultStatus::Failed, $r2->status);
    }
}
