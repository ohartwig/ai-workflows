<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Persistence\Model;

use Koh\AiWorkflows\Domain\Contract\WorkflowInstanceInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class WorkflowInstance extends AbstractEntity implements WorkflowInstanceInterface
{
    protected string $workflowName = '';
    protected string $workflowDefinition = '';
    protected string $status = 'pending';
    protected string $currentStep = '';
    protected string $contextData = '';
    protected string $inputData = '';
    protected string $outputData = '';
    protected int $startedBy = 0;
    protected int $startedAt = 0;
    protected int $completedAt = 0;
    protected string $errorMessage = '';

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function setWorkflowName(string $workflowName): void
    {
        $this->workflowName = $workflowName;
    }

    public function getWorkflowDefinition(): string
    {
        return $this->workflowDefinition;
    }

    public function setWorkflowDefinition(string $workflowDefinition): void
    {
        $this->workflowDefinition = $workflowDefinition;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCurrentStep(): string
    {
        return $this->currentStep;
    }

    public function setCurrentStep(string $currentStep): void
    {
        $this->currentStep = $currentStep;
    }

    public function getContextData(): string
    {
        return $this->contextData;
    }

    public function setContextData(string $contextData): void
    {
        $this->contextData = $contextData;
    }

    public function getInputData(): string
    {
        return $this->inputData;
    }

    public function setInputData(string $inputData): void
    {
        $this->inputData = $inputData;
    }

    public function getOutputData(): string
    {
        return $this->outputData;
    }

    public function setOutputData(string $outputData): void
    {
        $this->outputData = $outputData;
    }

    public function getStartedBy(): int
    {
        return $this->startedBy;
    }

    public function setStartedBy(int $startedBy): void
    {
        $this->startedBy = $startedBy;
    }

    public function getStartedAt(): int
    {
        return $this->startedAt;
    }

    public function setStartedAt(int $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getCompletedAt(): int
    {
        return $this->completedAt;
    }

    public function setCompletedAt(int $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }
}
