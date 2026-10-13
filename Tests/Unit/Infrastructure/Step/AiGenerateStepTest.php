<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Contract\AiClientInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Infrastructure\Step\AiGenerateStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AiGenerateStepTest extends TestCase
{
    #[Test]
    public function typeIsAiGenerate(): void
    {
        self::assertSame('ai_generate', new AiGenerateStep($this->createMock(AiClientInterface::class))->getType());
    }

    #[Test]
    public function executesSuccessfully(): void
    {
        $client = $this->createMock(AiClientInterface::class);
        $client->method('generate')->willReturn('Generated');
        $r = new AiGenerateStep($client)->execute(new StepDefinition('g1', 'ai_generate', ['prompt' => 'Write']), new WorkflowContext());
        self::assertTrue($r->isSuccessful());
        self::assertSame('Generated', $r->output['content']);
    }

    #[Test]
    public function returnsErrorOnException(): void
    {
        $client = $this->createMock(AiClientInterface::class);
        $client->method('generate')->willThrowException(new \RuntimeException('API error'));
        $r = new AiGenerateStep($client)->execute(new StepDefinition('g1', 'ai_generate', ['prompt' => 'x']), new WorkflowContext());
        self::assertFalse($r->isSuccessful());
        self::assertStringContainsString('API error', $r->message);
    }

    #[Test]
    public function validateRequiresPrompt(): void
    {
        self::assertNotEmpty(new AiGenerateStep($this->createMock(AiClientInterface::class))->validate(new StepDefinition('g1', 'ai_generate', [])));
    }
}
