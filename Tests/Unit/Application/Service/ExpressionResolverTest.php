<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Application\Service;

use Koh\AiWorkflows\Application\Service\ExpressionResolver;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExpressionResolverTest extends TestCase
{
    private ExpressionResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ExpressionResolver();
    }

    #[Test]
    public function resolvesSimpleInputExpression(): void
    {
        $context = new WorkflowContext(input: ['topic' => 'AI']);
        self::assertSame('AI', $this->resolver->resolve('{{ input.topic }}', $context));
    }

    #[Test]
    public function resolvesStepOutputExpression(): void
    {
        $context = new WorkflowContext()
            ->withStepResult('gen', StepResult::success(['content' => 'Hello']));
        self::assertSame('Hello', $this->resolver->resolve('{{ steps.gen.output.content }}', $context));
    }

    #[Test]
    public function resolvesInlineExpressions(): void
    {
        $context = new WorkflowContext(input: ['name' => 'World']);
        self::assertSame('Hello World!', $this->resolver->resolve('Hello {{ input.name }}!', $context));
    }

    #[Test]
    public function resolvesMultipleInlineExpressions(): void
    {
        $context = new WorkflowContext(input: ['first' => 'John', 'last' => 'Doe']);
        self::assertSame('John Doe', $this->resolver->resolve('{{ input.first }} {{ input.last }}', $context));
    }

    #[Test]
    public function returnsNullForUnresolvableExpression(): void
    {
        $context = new WorkflowContext();
        self::assertNull($this->resolver->resolve('{{ input.nonexistent }}', $context));
    }

    #[Test]
    public function returnsOriginalStringWithoutExpressions(): void
    {
        $context = new WorkflowContext();
        self::assertSame('plain text', $this->resolver->resolve('plain text', $context));
    }

    #[Test]
    public function resolvesArrayValues(): void
    {
        $context = new WorkflowContext(input: ['topic' => 'AI', 'lang' => 'en']);
        $result = $this->resolver->resolve(['prompt' => '{{ input.topic }}', 'language' => '{{ input.lang }}'], $context);
        self::assertSame(['prompt' => 'AI', 'language' => 'en'], $result);
    }

    #[Test]
    public function passesNonStringNonArrayValuesThrough(): void
    {
        $context = new WorkflowContext();
        self::assertSame(42, $this->resolver->resolve(42, $context));
        self::assertTrue($this->resolver->resolve(true, $context));
        self::assertNull($this->resolver->resolve(null, $context));
    }

    #[Test]
    public function fullExpressionReturnsArrayDirectly(): void
    {
        $context = new WorkflowContext(input: ['list' => ['a', 'b', 'c']]);
        self::assertSame(['a', 'b', 'c'], $this->resolver->resolve('{{ input.list }}', $context));
    }

    #[Test]
    public function inlineExpressionWithArrayValueSerializesToJson(): void
    {
        $context = new WorkflowContext(input: ['list' => ['a', 'b', 'c']]);
        self::assertSame('Items: ["a","b","c"]', $this->resolver->resolve('Items: {{ input.list }}', $context));
    }

    #[Test]
    public function resolvesVariableExpression(): void
    {
        $context = new WorkflowContext(variables: ['mode' => 'dark']);
        self::assertSame('dark', $this->resolver->resolve('{{ variables.mode }}', $context));
    }
}
