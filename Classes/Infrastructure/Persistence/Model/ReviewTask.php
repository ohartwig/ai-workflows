<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Persistence\Model;

use Koh\AiWorkflows\Domain\Contract\ReviewTaskInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class ReviewTask extends AbstractEntity implements ReviewTaskInterface
{
    protected int $workflowInstance = 0;
    protected string $stepId = '';
    protected string $title = '';
    protected string $description = '';
    protected string $contentData = '';
    protected string $status = 'pending';
    protected int $reviewer = 0;
    protected string $reviewComment = '';
    protected int $deadline = 0;
    protected int $reviewedAt = 0;

    public function getWorkflowInstance(): int
    {
        return $this->workflowInstance;
    }

    public function setWorkflowInstance(int $workflowInstance): void
    {
        $this->workflowInstance = $workflowInstance;
    }

    public function getStepId(): string
    {
        return $this->stepId;
    }

    public function setStepId(string $stepId): void
    {
        $this->stepId = $stepId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getContentData(): string
    {
        return $this->contentData;
    }

    public function setContentData(string $contentData): void
    {
        $this->contentData = $contentData;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getReviewer(): int
    {
        return $this->reviewer;
    }

    public function setReviewer(int $reviewer): void
    {
        $this->reviewer = $reviewer;
    }

    public function getReviewComment(): string
    {
        return $this->reviewComment;
    }

    public function setReviewComment(string $reviewComment): void
    {
        $this->reviewComment = $reviewComment;
    }

    public function getDeadline(): int
    {
        return $this->deadline;
    }

    public function setDeadline(int $deadline): void
    {
        $this->deadline = $deadline;
    }

    public function getReviewedAt(): int
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(int $reviewedAt): void
    {
        $this->reviewedAt = $reviewedAt;
    }
}
