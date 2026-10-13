<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

use Koh\AiWorkflows\Domain\Model\WorkflowDefinition;

interface WorkflowParserInterface
{
    /**
     * @throws \Koh\AiWorkflows\Application\Exception\InvalidWorkflowDefinitionException
     */
    public function parseFile(string $filePath): WorkflowDefinition;

    /**
     * @throws \Koh\AiWorkflows\Application\Exception\InvalidWorkflowDefinitionException
     */
    public function parseYaml(string $yamlContent): WorkflowDefinition;
}
