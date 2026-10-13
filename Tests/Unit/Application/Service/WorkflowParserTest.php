<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Application\Service;

use Koh\AiWorkflows\Application\Exception\InvalidWorkflowDefinitionException;
use Koh\AiWorkflows\Infrastructure\Parser\WorkflowParser;
use Koh\AiWorkflows\Domain\Enum\TriggerType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WorkflowParserTest extends TestCase
{
    private WorkflowParser $parser;

    protected function setUp(): void
    {
        $this->parser = new WorkflowParser();
    }

    #[Test]
    public function parsesValidYaml(): void
    {
        $yaml = "name: Test\ndescription: A test\ntrigger:\n  type: manual\nsteps:\n  step1:\n    type: ai_generate\n    parameters:\n      prompt: Hello";
        $def = $this->parser->parseYaml($yaml);
        self::assertSame('Test', $def->name);
        self::assertSame(TriggerType::Manual, $def->trigger->type);
        self::assertCount(1, $def->steps);
    }

    #[Test]
    public function throwsOnMissingName(): void
    {
        $this->expectException(InvalidWorkflowDefinitionException::class);
        $this->parser->parseYaml("steps:\n  step1:\n    type: webhook");
    }

    #[Test]
    public function throwsOnMissingSteps(): void
    {
        $this->expectException(InvalidWorkflowDefinitionException::class);
        $this->parser->parseYaml('name: No Steps');
    }

    #[Test]
    public function throwsOnStepWithoutType(): void
    {
        $this->expectException(InvalidWorkflowDefinitionException::class);
        $this->parser->parseYaml("name: Bad\nsteps:\n  step1:\n    parameters:\n      url: https://example.com");
    }

    #[Test]
    public function throwsOnInvalidYamlSyntax(): void
    {
        $this->expectException(InvalidWorkflowDefinitionException::class);
        $this->parser->parseYaml("valid:\n  key: value\n bad_indent: broken");
    }

    #[Test]
    public function throwsOnNonArrayYaml(): void
    {
        $this->expectException(InvalidWorkflowDefinitionException::class);
        $this->parser->parseYaml('just a string');
    }

    #[Test]
    public function throwsOnNonexistentFile(): void
    {
        $this->expectException(InvalidWorkflowDefinitionException::class);
        $this->parser->parseFile('/nonexistent/path/workflow.yaml');
    }

    #[Test]
    public function parsesMultipleSteps(): void
    {
        $yaml = "name: Multi\ntrigger:\n  type: schedule\n  schedule: '*/5 * * * *'\nsteps:\n  gen:\n    type: ai_generate\n  review:\n    type: human_review\n  pub:\n    type: distribute";
        $def = $this->parser->parseYaml($yaml);
        self::assertCount(3, $def->steps);
        self::assertSame(TriggerType::Schedule, $def->trigger->type);
    }
}
