<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Infrastructure\Step\ConditionStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConditionStepTest extends TestCase
{
    private ConditionStep $step;

    protected function setUp(): void
    {
        $this->step = new ConditionStep();
    }

    #[Test]
    public function typeIsCondition(): void
    {
        self::assertSame('condition', $this->step->getType());
    }

    #[Test]
    public function conditionMetWhenValueExists(): void
    {
        $r = $this->step->execute(new StepDefinition('c1', 'condition', ['if' => 'input.enabled', 'then' => 'go', 'else' => 'stop']), new WorkflowContext(input: ['enabled' => true]));
        self::assertTrue($r->output['conditionMet']);
        self::assertSame('go', $r->output['action']);
    }

    #[Test]
    public function conditionNotMetWhenEmpty(): void
    {
        $r = $this->step->execute(new StepDefinition('c1', 'condition', ['if' => 'input.enabled']), new WorkflowContext(input: ['enabled' => '']));
        self::assertFalse($r->output['conditionMet']);
    }

    #[Test]
    public function strictEqualsComparison(): void
    {
        $r = $this->step->execute(new StepDefinition('c1', 'condition', ['if' => 'input.count', 'equals' => '1']), new WorkflowContext(input: ['count' => 1]));
        self::assertFalse($r->output['conditionMet']); // int 1 !== string "1"
    }

    #[Test]
    public function validateRequiresIf(): void
    {
        self::assertNotEmpty($this->step->validate(new StepDefinition('c1', 'condition', [])));
    }
}
