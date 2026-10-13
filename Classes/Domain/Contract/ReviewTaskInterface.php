<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

/**
 * A review task as the domain sees it.
 *
 * The counterpart to AiTaskInterface and WorkflowInstanceInterface, which have
 * existed all along — this one was missing, so ReviewTaskRepositoryInterface
 * had to type its returns as plain `object`. Everything reading them, notably
 * ReviewTimeoutCommand, then worked against a type with no methods on it.
 */
interface ReviewTaskInterface
{
    public function getUid(): ?int;

    public function getWorkflowInstance(): int;

    public function setWorkflowInstance(int $workflowInstance): void;

    public function getStepId(): string;

    public function setStepId(string $stepId): void;

    public function getTitle(): string;

    public function setTitle(string $title): void;

    public function getDescription(): string;

    public function setDescription(string $description): void;

    public function getContentData(): string;

    public function setContentData(string $contentData): void;

    public function getStatus(): string;

    public function setStatus(string $status): void;

    public function getReviewer(): int;

    public function setReviewer(int $reviewer): void;

    public function getReviewComment(): string;

    public function setReviewComment(string $reviewComment): void;

    public function getDeadline(): int;

    public function setDeadline(int $deadline): void;

    public function getReviewedAt(): int;

    public function setReviewedAt(int $reviewedAt): void;
}
