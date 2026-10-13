<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Contract\ContentIntelligenceAdapterInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Infrastructure\Step\QualityGateStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QualityGateStepTest extends TestCase
{
    #[Test]
    public function passesWhenAboveThreshold(): void
    {
        $a = $this->createMock(ContentIntelligenceAdapterInterface::class);
        $a->method('analyzeContent')->willReturn(['passed' => true, 'score' => 0.95, 'checks' => []]);
        $r = new QualityGateStep($a)->execute(new StepDefinition('q1', 'quality_gate', ['content' => 'Good', 'threshold' => 0.8]), new WorkflowContext());
        self::assertTrue($r->isSuccessful());
        self::assertTrue($r->output['passed']);
    }

    #[Test]
    public function failsBelowThreshold(): void
    {
        $a = $this->createMock(ContentIntelligenceAdapterInterface::class);
        $a->method('analyzeContent')->willReturn(['passed' => false, 'score' => 0.3, 'checks' => []]);
        $r = new QualityGateStep($a)->execute(new StepDefinition('q1', 'quality_gate', ['content' => 'Bad', 'threshold' => 0.8]), new WorkflowContext());
        self::assertFalse($r->isSuccessful());
        self::assertStringContainsString('below threshold', $r->message);
    }

    #[Test]
    public function validateRequiresContent(): void
    {
        self::assertNotEmpty(new QualityGateStep($this->createMock(ContentIntelligenceAdapterInterface::class))->validate(new StepDefinition('q1', 'quality_gate', [])));
    }
}
