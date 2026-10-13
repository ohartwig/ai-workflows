<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Presentation\EventListener;

use Koh\AiWorkflows\Application\Event\ReviewRequestedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowCompletedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowFailedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowStartedEvent;
use Koh\AiWorkflows\Application\Service\NotificationDispatcher;
use TYPO3\CMS\Core\Attribute\AsEventListener;

final class WorkflowNotificationListener
{
    public function __construct(
        private readonly NotificationDispatcher $notificationDispatcher,
    ) {}

    #[AsEventListener]
    public function onWorkflowStarted(WorkflowStartedEvent $event): void
    {
        $this->notificationDispatcher->dispatch(
            'backend',
            'Workflow Started',
            sprintf('Workflow "%s" (Instance #%d) has been started.', $event->workflowName, $event->workflowInstanceUid),
            context: ['severity' => 'info'],
        );
    }

    #[AsEventListener]
    public function onWorkflowCompleted(WorkflowCompletedEvent $event): void
    {
        $this->notificationDispatcher->dispatch(
            'backend',
            'Workflow Completed',
            sprintf('Workflow "%s" (Instance #%d) has completed successfully.', $event->workflowName, $event->workflowInstanceUid),
            context: ['severity' => 'success'],
        );
    }

    #[AsEventListener]
    public function onWorkflowFailed(WorkflowFailedEvent $event): void
    {
        $this->notificationDispatcher->dispatch(
            'backend',
            'Workflow Failed',
            sprintf('Workflow instance #%d failed at step "%s": %s', $event->workflowInstanceUid, $event->failedStepId, $event->errorMessage),
            context: ['severity' => 'error'],
        );
    }

    #[AsEventListener]
    public function onReviewRequested(ReviewRequestedEvent $event): void
    {
        $this->notificationDispatcher->dispatch(
            'backend',
            'Review Required',
            sprintf('A new review has been requested: "%s" (Task #%d)', $event->title, $event->reviewTaskUid),
            context: ['severity' => 'warning'],
        );
    }
}
