<?php

/*
 * SPDX-FileCopyrightText: 2026 Moselwal Digitalagentur <info@moselwal.de>
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Step;

use Koh\AiWorkflows\Application\Event\AiTaskCreatedEvent;
use Koh\AiWorkflows\Domain\Contract\AiTaskRepositoryInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\AiTask;
use Psr\EventDispatcher\EventDispatcherInterface;

final class AiTaskStep extends AbstractStep
{
    public function __construct(
        private readonly AiTaskRepositoryInterface $aiTaskRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function getType(): string
    {
        return 'ai_task';
    }

    public function isBlocking(): bool
    {
        return true;
    }

    public function execute(StepDefinition $definition, WorkflowContext $context): StepResult
    {
        $prompt = (string) $this->requireParameter($definition, 'prompt');
        $taskType = (string) $this->getParameter($definition, 'task_type', 'generate');
        $contextKeys = (array) $this->getParameter($definition, 'context_keys', []);
        $expectedSchema = (array) $this->getParameter($definition, 'expected_schema', []);
        $workflowInstanceUid = (int) $context->getVariable('workflowInstanceUid', 0);

        // Gather context from previous steps
        $taskContext = [];
        foreach ($contextKeys as $key => $expression) {
            $taskContext[$key] = $context->resolveValue((string) $expression);
        }

        $aiTask = new AiTask();
        $aiTask->setWorkflowInstance($workflowInstanceUid);
        $aiTask->setStepId($definition->id);
        $aiTask->setTaskType($taskType);
        $aiTask->setPrompt($prompt);
        $aiTask->setContextData(json_encode($taskContext, JSON_THROW_ON_ERROR));
        $aiTask->setExpectedSchema(json_encode($expectedSchema, JSON_THROW_ON_ERROR));
        $aiTask->setStatus('pending');
        $aiTask->setCreatedAt(time());

        $this->aiTaskRepository->add($aiTask);

        $this->eventDispatcher->dispatch(new AiTaskCreatedEvent(
            workflowInstanceUid: $workflowInstanceUid,
            aiTaskUid: (int) $aiTask->getUid(),
            taskType: $taskType,
            prompt: $prompt,
        ));

        return StepResult::blocked('Waiting for AI agent');
    }

    public function validate(StepDefinition $definition): array
    {
        $errors = [];
        if (!isset($definition->parameters['prompt'])) {
            $errors[] = 'Parameter "prompt" is required for ai_task step';
        }

        return $errors;
    }
}
