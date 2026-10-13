<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Infrastructure\Step\DistributeStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DistributeStepTest extends TestCase
{
    private DistributeStep $step;

    protected function setUp(): void
    {
        $this->step = new DistributeStep();
    }

    #[Test]
    public function typeIsDistribute(): void
    {
        self::assertSame('distribute', $this->step->getType());
    }

    #[Test]
    public function distributesToTargets(): void
    {
        $r = $this->step->execute(new StepDefinition('d1', 'distribute', ['content' => 'Hello', 'targets' => ['web', 'social']]), new WorkflowContext());
        self::assertTrue($r->isSuccessful());
        self::assertCount(2, $r->output['distributions']);
        self::assertArrayHasKey('web', $r->output['distributions']);
    }

    #[Test]
    public function validateRequiresContent(): void
    {
        self::assertNotEmpty($this->step->validate(new StepDefinition('d1', 'distribute', [])));
    }
}
