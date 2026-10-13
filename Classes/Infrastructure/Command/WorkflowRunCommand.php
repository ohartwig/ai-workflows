<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Command;

use Koh\AiWorkflows\Application\Service\WorkflowEngine;
use Koh\AiWorkflows\Domain\Contract\WorkflowDiscoveryInterface;
use Koh\AiWorkflows\Domain\Enum\WorkflowStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aiworkflows:run',
    description: 'Run an AI workflow by name',
)]
final class WorkflowRunCommand extends Command
{
    public function __construct(
        private readonly WorkflowDiscoveryInterface $workflowDiscovery,
        private readonly WorkflowEngine $workflowEngine,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The workflow name (without .yaml extension)')
            ->addOption('input', 'i', InputOption::VALUE_OPTIONAL, 'Input data as JSON string', '{}')
            ->addOption('directory', 'd', InputOption::VALUE_OPTIONAL, 'Workflow directory', 'EXT:ai_workflows/Resources/Private/Workflows/');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $workflowName = $input->getArgument('name');
        $directory = $input->getOption('directory');

        $io->title(sprintf('Running workflow: %s', $workflowName));

        $workflow = $this->workflowDiscovery->getWorkflow($directory, $workflowName);
        if (null === $workflow) {
            $io->error(sprintf('Workflow "%s" not found in directory "%s"', $workflowName, $directory));

            return Command::FAILURE;
        }

        $inputJson = $input->getOption('input');
        try {
            $inputData = json_decode($inputJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $io->error(sprintf('Invalid JSON input: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $io->section('Workflow Details');
        $io->table(
            ['Property', 'Value'],
            [
                ['Name', $workflow->name],
                ['Description', $workflow->description],
                ['Trigger', $workflow->trigger->type->value],
                ['Steps', (string) count($workflow->steps)],
            ],
        );

        $io->section('Executing...');

        try {
            $instance = $this->workflowEngine->start($workflow, $inputData, 0);
        } catch (\Throwable $e) {
            $io->error(sprintf('Workflow execution failed: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $status = WorkflowStatus::from($instance->getStatus());

        match ($status) {
            WorkflowStatus::Completed => $io->success(sprintf(
                'Workflow completed successfully (Instance #%d)',
                $instance->getUid(),
            )),
            WorkflowStatus::Paused => $io->warning(sprintf(
                'Workflow paused at step "%s" — awaiting human review (Instance #%d)',
                $instance->getCurrentStep(),
                $instance->getUid(),
            )),
            WorkflowStatus::Failed => $io->error(sprintf(
                'Workflow failed at step "%s": %s (Instance #%d)',
                $instance->getCurrentStep(),
                $instance->getErrorMessage(),
                $instance->getUid(),
            )),
            default => $io->note(sprintf(
                'Workflow status: %s (Instance #%d)',
                $status->value,
                $instance->getUid(),
            )),
        };

        return WorkflowStatus::Failed === $status ? Command::FAILURE : Command::SUCCESS;
    }
}
