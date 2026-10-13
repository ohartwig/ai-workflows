<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Domain\Model;

use Koh\AiWorkflows\Domain\Model\StepDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StepDefinitionTest extends TestCase
{
    #[Test]
    public function constructorSetsProperties(): void
    {
        $step = new StepDefinition(id: 'gen', type: 'ai_generate', parameters: ['prompt' => 'Hi']);
        self::assertSame('gen', $step->id);
        self::assertSame('ai_generate', $step->type);
        self::assertSame(['prompt' => 'Hi'], $step->parameters);
    }

    #[Test]
    public function fromArrayCreatesInstance(): void
    {
        $step = StepDefinition::fromArray('review', ['type' => 'human_review', 'parameters' => ['title' => 'Check']]);
        self::assertSame('review', $step->id);
        self::assertSame('human_review', $step->type);
        self::assertSame(['title' => 'Check'], $step->parameters);
    }

    #[Test]
    public function fromArrayHandlesMissingKeys(): void
    {
        $step = StepDefinition::fromArray('minimal', ['type' => 'webhook']);
        self::assertSame([], $step->parameters);
        self::assertSame([], $step->output);
        self::assertSame([], $step->condition);
    }

    #[Test]
    public function toArrayRoundTrip(): void
    {
        $step = new StepDefinition(id: 'test', type: 'ai_generate', parameters: ['prompt' => 'Hi']);
        $array = $step->toArray();
        self::assertSame('test', $array['id']);
        self::assertSame('ai_generate', $array['type']);
    }
}
