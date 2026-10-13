<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Discovery;

use Koh\AiWorkflows\Domain\Contract\WorkflowDiscoveryInterface;
use Koh\AiWorkflows\Domain\Contract\WorkflowParserInterface;
use Koh\AiWorkflows\Domain\Model\WorkflowDefinition;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class WorkflowDiscovery implements WorkflowDiscoveryInterface
{
    public function __construct(
        private readonly WorkflowParserInterface $parser,
    ) {}

    /**
     * @return array<string, WorkflowDefinition>
     */
    public function discoverWorkflows(string $directory): array
    {
        $resolvedPath = GeneralUtility::getFileAbsFileName($directory);
        if ('' === $resolvedPath || !is_dir($resolvedPath)) {
            return [];
        }

        $workflows = [];
        $pattern = rtrim($resolvedPath, '/') . '/*.yaml';
        $files = glob($pattern);
        if (false === $files) {
            return [];
        }
        foreach ($files as $file) {
            $workflows[basename($file, '.yaml')] = $this->parser->parseFile($file);
        }

        return $workflows;
    }

    public function getWorkflow(string $directory, string $name): ?WorkflowDefinition
    {
        $resolvedPath = GeneralUtility::getFileAbsFileName($directory);
        $filePath = rtrim($resolvedPath, '/') . '/' . $name . '.yaml';

        if (!file_exists($filePath)) {
            return null;
        }

        return $this->parser->parseFile($filePath);
    }
}
