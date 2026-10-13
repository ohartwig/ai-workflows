<?php

/*
 * SPDX-FileCopyrightText: 2026 Moselwal Digitalagentur <info@moselwal.de>
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Persistence\Model;

use Koh\AiWorkflows\Domain\Contract\AiTaskInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class AiTask extends AbstractEntity implements AiTaskInterface
{
    protected int $workflowInstance = 0;
    protected string $stepId = '';
    protected string $taskType = 'generate';
    protected string $prompt = '';
    protected string $contextData = '';
    protected string $expectedSchema = '';
    protected string $responseData = '';
    protected string $status = 'pending';
    protected int $createdAt = 0;
    protected int $completedAt = 0;
    protected string $completedBy = '';

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

    public function getTaskType(): string
    {
        return $this->taskType;
    }

    public function setTaskType(string $taskType): void
    {
        $this->taskType = $taskType;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): void
    {
        $this->prompt = $prompt;
    }

    public function getContextData(): string
    {
        return $this->contextData;
    }

    public function setContextData(string $contextData): void
    {
        $this->contextData = $contextData;
    }

    public function getExpectedSchema(): string
    {
        return $this->expectedSchema;
    }

    public function setExpectedSchema(string $expectedSchema): void
    {
        $this->expectedSchema = $expectedSchema;
    }

    public function getResponseData(): string
    {
        return $this->responseData;
    }

    public function setResponseData(string $responseData): void
    {
        $this->responseData = $responseData;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCompletedAt(): int
    {
        return $this->completedAt;
    }

    public function setCompletedAt(int $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getCompletedBy(): string
    {
        return $this->completedBy;
    }

    public function setCompletedBy(string $completedBy): void
    {
        $this->completedBy = $completedBy;
    }
}
