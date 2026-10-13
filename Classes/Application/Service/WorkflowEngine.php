<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Application\Service;

use Koh\AiWorkflows\Application\Event\StepCompletedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowCompletedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowFailedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowStartedEvent;
use Koh\AiWorkflows\Application\Exception\WorkflowExecutionException;
use Koh\AiWorkflows\Domain\Contract\WorkflowInstanceFactoryInterface;
use Koh\AiWorkflows\Domain\Contract\WorkflowInstanceInterface;
use Koh\AiWorkflows\Domain\Contract\WorkflowInstanceRepositoryInterface;
use Koh\AiWorkflows\Domain\Enum\StepResultStatus;
use Koh\AiWorkflows\Domain\Enum\WorkflowStatus;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Domain\Model\WorkflowDefinition;
use Psr\EventDispatcher\EventDispatcherInterface;

final class WorkflowEngine
{
    public function __construct(
        private readonly StepExecutor $stepExecutor,
        private readonly WorkflowInstanceRepositoryInterface $workflowInstanceRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly WorkflowInstanceFactoryInterface $instanceFactory,
    ) {}

    /**
     * @param array<string, mixed> $input
     */
    public function start(WorkflowDefinition $definition, array $input = [], int $startedBy = 0): WorkflowInstanceInterface
    {
        $instance = $this->instanceFactory->create();
        $instance->setWorkflowName($definition->name);
        $instance->setWorkflowDefinition(json_encode($definition->toArray(), JSON_THROW_ON_ERROR));
        $instance->setStatus(WorkflowStatus::Running->value);
        $instance->setInputData(json_encode($input, JSON_THROW_ON_ERROR));
        $instance->setStartedBy($startedBy);
        $instance->setStartedAt(time());

        $this->workflowInstanceRepository->add($instance);

        $this->eventDispatcher->dispatch(new WorkflowStartedEvent(
            workflowInstanceUid: (int) $instance->getUid(),
            workflowName: $definition->name,
            input: $input,
            startedBy: $startedBy,
        ));

        $context = (new WorkflowContext(input: $input))
            ->withVariable('workflowInstanceUid', (int) $instance->getUid());

        return $this->executeSteps($instance, $definition, $context);
    }

    /**
     * @param array<string, mixed> $additionalData
     */
    public function resume(int $workflowInstanceUid, array $additionalData = []): WorkflowInstanceInterface
    {
        $instance = $this->workflowInstanceRepository->findByUid($workflowInstanceUid);
        if (null === $instance) {
            throw new WorkflowExecutionException(sprintf('Workflow instance %d not found', $workflowInstanceUid), 1711300001);
        }

        if ($instance->getStatus() !== WorkflowStatus::Paused->value) {
            throw new WorkflowExecutionException(sprintf('Workflow instance %d is not paused (status: %s)', $workflowInstanceUid, $instance->getStatus()), 1711300002);
        }

        $definition = WorkflowDefinition::fromArray(
            json_decode($instance->getWorkflowDefinition(), true, 512, JSON_THROW_ON_ERROR),
        );

        $context = WorkflowContext::fromArray(
            json_decode($instance->getContextData(), true, 512, JSON_THROW_ON_ERROR),
        )->withVariable('workflowInstanceUid', $workflowInstanceUid);

        foreach ($additionalData as $key => $value) {
            $context = $context->withVariable($key, $value);
        }

        $instance->setStatus(WorkflowStatus::Running->value);
        $this->workflowInstanceRepository->update($instance);

        return $this->executeSteps($instance, $definition, $context, $instance->getCurrentStep());
    }

    public function cancel(int $workflowInstanceUid): void
    {
        $instance = $this->workflowInstanceRepository->findByUid($workflowInstanceUid);
        if (null === $instance) {
            throw new WorkflowExecutionException(sprintf('Workflow instance %d not found', $workflowInstanceUid), 1711300003);
        }

        $instance->setStatus(WorkflowStatus::Cancelled->value);
        $instance->setCompletedAt(time());
        $this->workflowInstanceRepository->update($instance);
    }

    private function executeSteps(
        WorkflowInstanceInterface $instance,
        WorkflowDefinition $definition,
        WorkflowContext $context,
        string $resumeFromStep = '',
    ): WorkflowInstanceInterface {
        $steps = $definition->steps;
        $startExecution = '' === $resumeFromStep;

        foreach ($steps as $stepDefinition) {
            if (!$startExecution) {
                if ($stepDefinition->id === $resumeFromStep) {
                    $startExecution = true;
                    continue;
                }
                continue;
            }

            $skipResult = $this->skipIfConditionNotMet($stepDefinition, $context);
            if (null !== $skipResult) {
                $context = $skipResult;
                continue;
            }

            $instance->setCurrentStep($stepDefinition->id);
            $this->workflowInstanceRepository->update($instance);

            try {
                $result = $this->stepExecutor->execute($stepDefinition, $context);
            } catch (\Throwable $e) {
                return $this->markFailed($instance, $stepDefinition->id, $e->getMessage());
            }

            $context = $context->withStepResult($stepDefinition->id, $result);

            $this->eventDispatcher->dispatch(new StepCompletedEvent(
                workflowInstanceUid: (int) $instance->getUid(),
                stepId: $stepDefinition->id,
                stepType: $stepDefinition->type,
                result: $result,
            ));

            if (StepResultStatus::Blocked === $result->status) {
                return $this->pauseWorkflow($instance, $context);
            }

            if (StepResultStatus::Failed === $result->status) {
                return $this->markFailed($instance, $stepDefinition->id, $result->message);
            }
        }

        return $this->completeWorkflow($instance, $definition, $context);
    }

    /**
     * Returns a context with the "skipped" result appended when the step's
     * condition evaluates to false; returns null when the step should run
     * normally.
     */
    private function skipIfConditionNotMet(
        StepDefinition $stepDefinition,
        WorkflowContext $context,
    ): ?WorkflowContext {
        if ([] === $stepDefinition->condition) {
            return null;
        }

        if (!$this->evaluateCondition($stepDefinition->condition, $context)) {
            return $context->withStepResult($stepDefinition->id, StepResult::skipped('Condition not met'));
        }

        return null;
    }

    private function pauseWorkflow(
        WorkflowInstanceInterface $instance,
        WorkflowContext $context,
    ): WorkflowInstanceInterface {
        $instance->setStatus(WorkflowStatus::Paused->value);
        $instance->setContextData(json_encode($context->toArray(), JSON_THROW_ON_ERROR));
        $this->workflowInstanceRepository->update($instance);

        return $instance;
    }

    private function markFailed(
        WorkflowInstanceInterface $instance,
        string $stepId,
        string $errorMessage,
    ): WorkflowInstanceInterface {
        $instance->setStatus(WorkflowStatus::Failed->value);
        $instance->setErrorMessage($errorMessage);
        $instance->setCompletedAt(time());
        $this->workflowInstanceRepository->update($instance);

        $this->eventDispatcher->dispatch(new WorkflowFailedEvent(
            workflowInstanceUid: (int) $instance->getUid(),
            failedStepId: $stepId,
            errorMessage: $errorMessage,
        ));

        return $instance;
    }

    private function completeWorkflow(
        WorkflowInstanceInterface $instance,
        WorkflowDefinition $definition,
        WorkflowContext $context,
    ): WorkflowInstanceInterface {
        $instance->setStatus(WorkflowStatus::Completed->value);
        $instance->setContextData(json_encode($context->toArray(), JSON_THROW_ON_ERROR));
        $instance->setOutputData(json_encode($this->collectOutputs($context, $definition), JSON_THROW_ON_ERROR));
        $instance->setCompletedAt(time());
        $this->workflowInstanceRepository->update($instance);

        $this->eventDispatcher->dispatch(new WorkflowCompletedEvent(
            workflowInstanceUid: (int) $instance->getUid(),
            workflowName: $definition->name,
            finalContext: $context,
        ));

        return $instance;
    }

    /**
     * @param array<string, mixed> $condition
     */
    private function evaluateCondition(array $condition, WorkflowContext $context): bool
    {
        if (isset($condition['if'])) {
            $value = $context->resolveValue($condition['if']);
            $expected = $condition['equals'] ?? null;
            if (null !== $expected) {
                return $value === $expected;
            }

            return null !== $value && '' !== $value && false !== $value;
        }

        return true;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function collectOutputs(WorkflowContext $context, WorkflowDefinition $definition): array
    {
        $outputs = [];
        foreach ($definition->steps as $step) {
            $result = $context->getStepResult($step->id);
            if (null !== $result && $result->isSuccessful() && [] !== $result->output) {
                $outputs[$step->id] = $result->output;
            }
        }

        return $outputs;
    }
}
