<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Domain\Model;

use Koh\AiWorkflows\Domain\Enum\TriggerType;
use Koh\AiWorkflows\Domain\Model\WorkflowDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowTrigger;
use Koh\AiWorkflows\Domain\Model\InputDefinition;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WorkflowDefinitionTest extends TestCase
{
    #[Test]
    public function fromArrayCreatesFullDefinition(): void
    {
        $data = [
            'name' => 'Content Pipeline',
            'description' => 'Generate and review',
            'trigger' => ['type' => 'manual'],
            'steps' => [
                'gen' => ['type' => 'ai_generate', 'parameters' => ['prompt' => 'Hi']],
                'review' => ['type' => 'human_review'],
            ],
            'notifications' => ['on_complete' => ['channel' => 'email']],
        ];
        $def = WorkflowDefinition::fromArray($data);
        self::assertSame('Content Pipeline', $def->name);
        self::assertSame(TriggerType::Manual, $def->trigger->type);
        self::assertCount(2, $def->steps);
        self::assertSame('gen', $def->steps[0]->id);
    }

    #[Test]
    public function toArrayAndFromArrayRoundTrip(): void
    {
        $original = new WorkflowDefinition(
            name: 'Test',
            description: 'A test',
            trigger: new WorkflowTrigger(TriggerType::Schedule, '*/5 * * * *'),
            inputs: [new InputDefinition('topic', 'string', 'Topic', required: true)],
            steps: [new StepDefinition('s1', 'ai_generate', ['prompt' => 'test'])],
        );
        $restored = WorkflowDefinition::fromArray($original->toArray());
        self::assertSame($original->name, $restored->name);
        self::assertCount(1, $restored->steps);
        self::assertSame('s1', $restored->steps[0]->id);

        // The inputs are what a workflow asks the operator for. Losing them on a
        // round trip through the stored array would silently drop the form.
        self::assertCount(1, $restored->inputs);
        self::assertSame('topic', $restored->inputs[0]->name);
        self::assertSame('Topic', $restored->inputs[0]->label);
        self::assertTrue($restored->inputs[0]->required);
    }

    /**
     * fromArray() keys inputs by name, toArray() writes them back under that key.
     * A definition parsed from YAML must survive the trip unchanged.
     */
    #[Test]
    public function inputsAreParsedFromTheirKeyedForm(): void
    {
        $def = WorkflowDefinition::fromArray([
            'name' => 'Pipeline',
            'trigger' => ['type' => 'manual'],
            'inputs' => [
                'topic' => ['type' => 'string', 'label' => 'Topic', 'required' => true],
                'tone' => ['type' => 'select', 'options' => ['formal', 'casual']],
            ],
            'steps' => ['gen' => ['type' => 'ai_generate']],
        ]);

        self::assertCount(2, $def->inputs);
        self::assertSame(['topic', 'tone'], \array_map(
            static fn(InputDefinition $input): string => $input->name,
            $def->inputs,
        ));
        self::assertSame('tone', $def->inputs[1]->label, 'a missing label falls back to the input name');
        self::assertSame(['formal', 'casual'], $def->inputs[1]->options);
    }

    #[Test]
    public function aDefinitionWithoutInputsIsValid(): void
    {
        $def = WorkflowDefinition::fromArray([
            'name' => 'Pipeline',
            'trigger' => ['type' => 'manual'],
            'steps' => ['gen' => ['type' => 'ai_generate']],
        ]);

        self::assertSame([], $def->inputs);
        self::assertSame([], $def->toArray()['inputs']);
    }
}
