<?php

/*
 * SPDX-FileCopyrightText: 2026 Moselwal Digitalagentur <info@moselwal.de>
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

interface AiTaskInterface
{
    public function getUid(): ?int;

    public function getWorkflowInstance(): int;

    public function setWorkflowInstance(int $workflowInstance): void;

    public function getStepId(): string;

    public function setStepId(string $stepId): void;

    public function getTaskType(): string;

    public function setTaskType(string $taskType): void;

    public function getPrompt(): string;

    public function setPrompt(string $prompt): void;

    public function getContextData(): string;

    public function setContextData(string $contextData): void;

    public function getExpectedSchema(): string;

    public function setExpectedSchema(string $expectedSchema): void;

    public function getResponseData(): string;

    public function setResponseData(string $responseData): void;

    public function getStatus(): string;

    public function setStatus(string $status): void;

    public function getCreatedAt(): int;

    public function setCreatedAt(int $createdAt): void;

    public function getCompletedAt(): int;

    public function setCompletedAt(int $completedAt): void;

    public function getCompletedBy(): string;

    public function setCompletedBy(string $completedBy): void;
}
