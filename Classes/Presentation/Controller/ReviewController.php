<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Presentation\Controller;

use Koh\AiWorkflows\Application\Service\WorkflowEngine;
use Koh\AiWorkflows\Domain\Contract\ReviewTaskRepositoryInterface;
use Koh\AiWorkflows\Domain\Enum\ReviewStatus;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\ReviewTask;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

#[AsController]
class ReviewController extends ActionController
{
    public function __construct(
        private readonly ReviewTaskRepositoryInterface $reviewTaskRepository,
        private readonly WorkflowEngine $workflowEngine,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly PersistenceManagerInterface $persistenceManager,
    ) {}

    public function indexAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $pendingReviews = $this->reviewTaskRepository->findByStatus(ReviewStatus::Pending->value);
        $moduleTemplate->assignMultiple(['pendingReviews' => $pendingReviews]);

        return $moduleTemplate->renderResponse('Review/Index');
    }

    public function showAction(int $uid): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $reviewTask = $this->reviewTaskRepository->findByUid($uid);
        $moduleTemplate->assignMultiple(['reviewTask' => $reviewTask]);

        return $moduleTemplate->renderResponse('Review/Show');
    }

    public function approveAction(int $uid, string $comment = ''): ResponseInterface
    {
        /** @var ReviewTask|null $reviewTask */
        $reviewTask = $this->reviewTaskRepository->findByUid($uid);
        if (null === $reviewTask) {
            $this->addFlashMessage('Review task not found', 'Error', \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR);

            return $this->redirect('index');
        }

        $backendUser = $GLOBALS['BE_USER'];
        $reviewTask->setStatus(ReviewStatus::Approved->value);
        $reviewTask->setReviewer((int) ($backendUser->user['uid'] ?? 0));
        $reviewTask->setReviewComment($comment);
        $reviewTask->setReviewedAt(time());

        $this->reviewTaskRepository->update($reviewTask);
        $this->persistenceManager->persistAll();

        $this->workflowEngine->resume($reviewTask->getWorkflowInstance(), [
            'reviewResult' => 'approved',
            'reviewComment' => $comment,
        ]);

        $this->addFlashMessage('Review approved', 'Success');

        return $this->redirect('index');
    }

    public function rejectAction(int $uid, string $comment = ''): ResponseInterface
    {
        /** @var ReviewTask|null $reviewTask */
        $reviewTask = $this->reviewTaskRepository->findByUid($uid);
        if (null === $reviewTask) {
            $this->addFlashMessage('Review task not found', 'Error', \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR);

            return $this->redirect('index');
        }

        $backendUser = $GLOBALS['BE_USER'];
        $reviewTask->setStatus(ReviewStatus::Rejected->value);
        $reviewTask->setReviewer((int) ($backendUser->user['uid'] ?? 0));
        $reviewTask->setReviewComment($comment);
        $reviewTask->setReviewedAt(time());

        $this->reviewTaskRepository->update($reviewTask);
        $this->persistenceManager->persistAll();

        $this->workflowEngine->cancel($reviewTask->getWorkflowInstance());

        $this->addFlashMessage('Review rejected — workflow cancelled', 'Rejected');

        return $this->redirect('index');
    }
}
