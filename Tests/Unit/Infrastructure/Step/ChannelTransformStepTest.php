<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Contract\SemanticDeliveryAdapterInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Infrastructure\Step\ChannelTransformStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ChannelTransformStepTest extends TestCase
{
    #[Test]
    public function transformsContent(): void
    {
        $a = $this->createMock(SemanticDeliveryAdapterInterface::class);
        $a->method('transform')->willReturn('<h1>Hello</h1>');
        $r = new ChannelTransformStep($a)->execute(new StepDefinition('t1', 'channel_transform', ['content' => 'Hello', 'channel' => 'web']), new WorkflowContext());
        self::assertTrue($r->isSuccessful());
        self::assertSame('<h1>Hello</h1>', $r->output['content']);
        self::assertSame('web', $r->output['channel']);
    }

    #[Test]
    public function returnsErrorOnFailure(): void
    {
        $a = $this->createMock(SemanticDeliveryAdapterInterface::class);
        $a->method('transform')->willThrowException(new \RuntimeException('Transform error'));
        $r = new ChannelTransformStep($a)->execute(new StepDefinition('t1', 'channel_transform', ['content' => 'Hi', 'channel' => 'web']), new WorkflowContext());
        self::assertFalse($r->isSuccessful());
    }

    #[Test]
    public function validateRequiresContentAndChannel(): void
    {
        self::assertCount(2, new ChannelTransformStep($this->createMock(SemanticDeliveryAdapterInterface::class))->validate(new StepDefinition('t1', 'channel_transform', [])));
    }
}
