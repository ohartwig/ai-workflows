<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Presentation\Controller;

use Koh\AiWorkflows\Application\Service\WorkflowEngine;
use Koh\AiWorkflows\Domain\Contract\WorkflowDiscoveryInterface;
use Koh\AiWorkflows\Domain\Contract\WorkflowInstanceRepositoryInterface;
use Koh\AiWorkflows\Domain\Enum\WorkflowStatus;
use Koh\AiWorkflows\Domain\Model\WorkflowDefinition;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

#[AsController]
class WorkflowDashboardController extends ActionController
{
    public function __construct(
        private readonly WorkflowInstanceRepositoryInterface $workflowInstanceRepository,
        private readonly WorkflowDiscoveryInterface $workflowDiscovery,
        private readonly WorkflowEngine $workflowEngine,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function indexAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $workflows = $this->workflowDiscovery->discoverWorkflows(
            $this->settings['workflowDirectory'] ?? 'EXT:ai_workflows/Resources/Private/Workflows/',
        );

        $runningInstances = $this->workflowInstanceRepository->findByStatus(WorkflowStatus::Running->value);
        $pausedInstances = $this->workflowInstanceRepository->findByStatus(WorkflowStatus::Paused->value);
        $recentCompleted = $this->workflowInstanceRepository->findByStatus(WorkflowStatus::Completed->value);

        $moduleTemplate->assignMultiple([
            'workflows' => $workflows,
            'runningInstances' => $runningInstances,
            'pausedInstances' => $pausedInstances,
            'recentCompleted' => $recentCompleted,
        ]);

        return $moduleTemplate->renderResponse('WorkflowDashboard/Index');
    }

    public function showAction(int $uid): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $instance = $this->workflowInstanceRepository->findByUid($uid);

        if (null === $instance) {
            $this->addFlashMessage('Workflow instance not found.', 'Error', \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR);

            return $this->redirect('index');
        }

        $steps = [];
        $inputData = [];
        $outputData = [];
        $stepResults = [];
        $totalSteps = 0;
        $completedSteps = 0;

        $definitionJson = $instance->getWorkflowDefinition();
        if ('' !== $definitionJson) {
            $definition = WorkflowDefinition::fromArray(
                json_decode($definitionJson, true, 512, JSON_THROW_ON_ERROR),
            );
            foreach ($definition->steps as $stepDefinition) {
                $steps[] = [
                    'id' => $stepDefinition->id,
                    'type' => $stepDefinition->type,
                    'status' => 'pending',
                    'message' => '',
                    'output' => [],
                ];
            }
            $totalSteps = count($steps);
        }

        $inputJson = $instance->getInputData();
        if ('' !== $inputJson) {
            $inputData = (array) json_decode($inputJson, true, 512, JSON_THROW_ON_ERROR);
        }

        $outputJson = $instance->getOutputData();
        if ('' !== $outputJson) {
            $outputData = (array) json_decode($outputJson, true, 512, JSON_THROW_ON_ERROR);
        }

        $contextJson = $instance->getContextData();
        if ('' !== $contextJson) {
            $contextArray = (array) json_decode($contextJson, true, 512, JSON_THROW_ON_ERROR);
            $stepResults = $contextArray['stepResults'] ?? [];
        }

        foreach ($steps as &$step) {
            if (isset($stepResults[$step['id']])) {
                $result = $stepResults[$step['id']];
                $step['status'] = $result['status'] ?? 'pending';
                $step['message'] = $result['message'] ?? '';
                $step['output'] = $result['output'] ?? [];
                if ('completed' === $step['status'] || 'skipped' === $step['status']) {
                    ++$completedSteps;
                }
            } elseif ($step['id'] === $instance->getCurrentStep() && $instance->getStatus() === WorkflowStatus::Running->value) {
                $step['status'] = 'running';
            }
        }
        unset($step);

        $progressPercent = $totalSteps > 0 ? (int) round(($completedSteps / $totalSteps) * 100) : 0;

        $moduleTemplate->assignMultiple([
            'instance' => $instance,
            'steps' => $steps,
            'inputData' => $inputData,
            'outputData' => $outputData,
            'totalSteps' => $totalSteps,
            'completedSteps' => $completedSteps,
            'progressPercent' => $progressPercent,
        ]);

        return $moduleTemplate->renderResponse('WorkflowDashboard/Show');
    }

    public function startAction(string $workflowName = ''): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $workflows = $this->workflowDiscovery->discoverWorkflows(
            $this->settings['workflowDirectory'] ?? 'EXT:ai_workflows/Resources/Private/Workflows/',
        );

        $selectedWorkflow = null;
        if ('' !== $workflowName && isset($workflows[$workflowName])) {
            $selectedWorkflow = $workflows[$workflowName];
        }

        $workflowOptions = [];
        foreach ($workflows as $key => $def) {
            $workflowOptions[$key] = $def->name;
        }

        $moduleTemplate->assignMultiple([
            'workflows' => $workflows,
            'workflowOptions' => $workflowOptions,
            'workflowName' => $workflowName,
            'selectedWorkflow' => $selectedWorkflow,
        ]);

        return $moduleTemplate->renderResponse('WorkflowDashboard/Start');
    }

    /**
     * @param array<string, mixed> $workflowInput
     */
    public function executeAction(string $workflowName, array $workflowInput = []): ResponseInterface
    {
        $workflow = $this->workflowDiscovery->getWorkflow(
            $this->settings['workflowDirectory'] ?? 'EXT:ai_workflows/Resources/Private/Workflows/',
            $workflowName,
        );

        if (null === $workflow) {
            $this->addFlashMessage(
                'Workflow not found: ' . htmlspecialchars($workflowName),
                'Error',
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR,
            );

            return $this->redirect('index');
        }

        $input = $this->buildInputFromDefinition($workflow, $workflowInput);
        $errors = $this->validateInput($workflow, $input);

        if ([] !== $errors) {
            foreach ($errors as $error) {
                $this->addFlashMessage(
                    $error,
                    'Validation Error',
                    \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR,
                );
            }

            return $this->redirect('start', null, null, ['workflowName' => $workflowName]);
        }

        $backendUser = $GLOBALS['BE_USER'];
        $instance = $this->workflowEngine->start($workflow, $input, (int) ($backendUser->user['uid'] ?? 0));

        $this->addFlashMessage(
            sprintf('Workflow "%s" started (Instance #%d)', htmlspecialchars($workflowName), $instance->getUid()),
            'Workflow Started',
        );

        return $this->redirect('show', null, null, ['uid' => $instance->getUid()]);
    }

    /**
     * @param array<string, mixed> $rawInput
     *
     * @return array<string, mixed>
     */
    private function buildInputFromDefinition(WorkflowDefinition $workflow, array $rawInput): array
    {
        $input = [];
        foreach ($workflow->inputs as $definition) {
            $value = $rawInput[$definition->name] ?? null;

            if ((null === $value || '' === $value) && null !== $definition->default) {
                $value = $definition->default;
            }

            if (null !== $value && '' !== $value) {
                $input[$definition->name] = match ($definition->type) {
                    'number' => is_numeric($value) ? (float) $value : $value,
                    'integer' => is_numeric($value) ? (int) $value : $value,
                    'boolean' => (bool) $value,
                    default => (string) $value,
                };
            }
        }

        return $input;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return list<string>
     */
    private function validateInput(WorkflowDefinition $workflow, array $input): array
    {
        $errors = [];
        foreach ($workflow->inputs as $definition) {
            if ($definition->required && (!isset($input[$definition->name]) || '' === $input[$definition->name])) {
                $errors[] = sprintf('Field "%s" is required.', $definition->label);
            }

            if (
                [] !== $definition->options
                && isset($input[$definition->name])
                && '' !== $input[$definition->name]
                && !in_array((string) $input[$definition->name], $definition->options, true)
            ) {
                $errors[] = sprintf(
                    'Field "%s" must be one of: %s',
                    $definition->label,
                    implode(', ', $definition->options),
                );
            }
        }

        return $errors;
    }
}
