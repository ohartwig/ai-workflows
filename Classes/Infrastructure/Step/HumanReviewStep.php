<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Step;

use Koh\AiWorkflows\Application\Event\ReviewRequestedEvent;
use Koh\AiWorkflows\Domain\Contract\ReviewTaskRepositoryInterface;
use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\ReviewTask;
use Psr\EventDispatcher\EventDispatcherInterface;

final class HumanReviewStep extends AbstractStep
{
    public function __construct(
        private readonly ReviewTaskRepositoryInterface $reviewTaskRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function getType(): string
    {
        return 'human_review';
    }

    public function isBlocking(): bool
    {
        return true;
    }

    public function execute(StepDefinition $definition, WorkflowContext $context): StepResult
    {
        $title = (string) $this->getParameter($definition, 'title', 'Review Required');
        $description = (string) $this->getParameter($definition, 'description', '');
        $content = $this->getParameter($definition, 'content', '');
        $deadline = (int) $this->getParameter($definition, 'deadline', 86400);
        $workflowInstanceUid = (int) $context->getVariable('workflowInstanceUid', 0);

        $reviewTask = new ReviewTask();
        $reviewTask->setWorkflowInstance($workflowInstanceUid);
        $reviewTask->setStepId($definition->id);
        $reviewTask->setTitle($title);
        $reviewTask->setDescription($description);
        $reviewTask->setContentData(is_string($content) ? $content : json_encode($content, JSON_THROW_ON_ERROR));
        $reviewTask->setStatus('pending');
        $reviewTask->setDeadline(time() + $deadline);

        $this->reviewTaskRepository->add($reviewTask);

        $this->eventDispatcher->dispatch(new ReviewRequestedEvent(
            workflowInstanceUid: $workflowInstanceUid,
            reviewTaskUid: (int) $reviewTask->getUid(),
            title: $title,
        ));

        return StepResult::blocked('Waiting for human review');
    }

    public function validate(StepDefinition $definition): array
    {
        return [];
    }
}
