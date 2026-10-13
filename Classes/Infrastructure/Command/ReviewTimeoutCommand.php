<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Command;

use Koh\AiWorkflows\Application\Service\WorkflowEngine;
use Koh\AiWorkflows\Domain\Contract\ReviewTaskRepositoryInterface;
use Koh\AiWorkflows\Domain\Enum\ReviewStatus;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\ReviewTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

#[AsCommand(
    name: 'aiworkflows:review:timeout',
    description: 'Process overdue review tasks',
)]
final class ReviewTimeoutCommand extends Command
{
    public function __construct(
        private readonly ReviewTaskRepositoryInterface $reviewTaskRepository,
        private readonly WorkflowEngine $workflowEngine,
        private readonly PersistenceManagerInterface $persistenceManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Processing overdue review tasks');

        $overdueTasks = $this->reviewTaskRepository->findOverdue(time());

        if ([] === $overdueTasks) {
            $io->success('No overdue review tasks found.');

            return Command::SUCCESS;
        }

        $io->note(sprintf('Found %d overdue review task(s)', count($overdueTasks)));

        $processed = 0;
        foreach ($overdueTasks as $task) {
            /* @var ReviewTask $task */
            $task->setStatus(ReviewStatus::Timeout->value);
            $task->setReviewedAt(time());
            $task->setReviewComment('Automatically timed out');
            $this->reviewTaskRepository->update($task);

            try {
                $this->workflowEngine->cancel($task->getWorkflowInstance());
                $io->writeln(sprintf(
                    '  Timed out: Task #%d "%s" — workflow #%d cancelled',
                    $task->getUid(),
                    $task->getTitle(),
                    $task->getWorkflowInstance(),
                ));
            } catch (\Throwable $e) {
                $io->warning(sprintf(
                    '  Task #%d: could not cancel workflow #%d: %s',
                    $task->getUid(),
                    $task->getWorkflowInstance(),
                    $e->getMessage(),
                ));
            }

            ++$processed;
        }

        $this->persistenceManager->persistAll();

        $io->success(sprintf('Processed %d overdue review task(s)', $processed));

        return Command::SUCCESS;
    }
}
