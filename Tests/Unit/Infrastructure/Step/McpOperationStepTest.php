<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Contract\McpServerAdapterInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Infrastructure\Step\McpOperationStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class McpOperationStepTest extends TestCase
{
    #[Test]
    public function executesSuccessfully(): void
    {
        $a = $this->createMock(McpServerAdapterInterface::class);
        $a->method('executeOperation')->willReturn(['success' => true, 'data' => 'result']);
        $r = new McpOperationStep($a)->execute(new StepDefinition('m1', 'mcp_operation', ['operation' => 'content.fetch']), new WorkflowContext());
        self::assertTrue($r->isSuccessful());
    }

    #[Test]
    public function returnsErrorOnFailure(): void
    {
        $a = $this->createMock(McpServerAdapterInterface::class);
        $a->method('executeOperation')->willReturn(['success' => false, 'error' => 'Not found']);
        $r = new McpOperationStep($a)->execute(new StepDefinition('m1', 'mcp_operation', ['operation' => 'unknown']), new WorkflowContext());
        self::assertFalse($r->isSuccessful());
        self::assertStringContainsString('Not found', $r->message);
    }

    #[Test]
    public function validateRequiresOperation(): void
    {
        self::assertNotEmpty(new McpOperationStep($this->createMock(McpServerAdapterInterface::class))->validate(new StepDefinition('m1', 'mcp_operation', [])));
    }
}
