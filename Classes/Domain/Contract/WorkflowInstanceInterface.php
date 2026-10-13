<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

/**
 * Domain contract for workflow instance persistence.
 * Infrastructure implementations (e.g. Extbase entities) implement this interface.
 */
interface WorkflowInstanceInterface
{
    public function getUid(): ?int;

    public function getWorkflowName(): string;

    public function setWorkflowName(string $workflowName): void;

    public function getWorkflowDefinition(): string;

    public function setWorkflowDefinition(string $workflowDefinition): void;

    public function getStatus(): string;

    public function setStatus(string $status): void;

    public function getCurrentStep(): string;

    public function setCurrentStep(string $currentStep): void;

    public function getContextData(): string;

    public function setContextData(string $contextData): void;

    public function getInputData(): string;

    public function setInputData(string $inputData): void;

    public function getOutputData(): string;

    public function setOutputData(string $outputData): void;

    public function getStartedBy(): int;

    public function setStartedBy(int $startedBy): void;

    public function getStartedAt(): int;

    public function setStartedAt(int $startedAt): void;

    public function getCompletedAt(): int;

    public function setCompletedAt(int $completedAt): void;

    public function getErrorMessage(): string;

    public function setErrorMessage(string $errorMessage): void;
}
