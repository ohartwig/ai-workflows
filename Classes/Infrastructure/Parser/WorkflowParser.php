<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Parser;

use Koh\AiWorkflows\Application\Exception\InvalidWorkflowDefinitionException;
use Koh\AiWorkflows\Domain\Contract\WorkflowParserInterface;
use Koh\AiWorkflows\Domain\Model\WorkflowDefinition;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class WorkflowParser implements WorkflowParserInterface
{
    /**
     * @throws InvalidWorkflowDefinitionException
     */
    public function parseFile(string $filePath): WorkflowDefinition
    {
        if (!file_exists($filePath)) {
            throw new InvalidWorkflowDefinitionException(sprintf('Workflow file not found: %s', $filePath), 1711100001);
        }

        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new InvalidWorkflowDefinitionException(sprintf('Could not read workflow file: %s', $filePath), 1711100002);
        }

        return $this->parseYaml($content);
    }

    /**
     * @throws InvalidWorkflowDefinitionException
     */
    public function parseYaml(string $yamlContent): WorkflowDefinition
    {
        try {
            $data = Yaml::parse($yamlContent);
        } catch (ParseException $e) {
            throw new InvalidWorkflowDefinitionException(sprintf('Invalid YAML: %s', $e->getMessage()), 1711100003, $e);
        }

        if (!is_array($data)) {
            throw new InvalidWorkflowDefinitionException('Workflow definition must be an array', 1711100004);
        }

        $this->validate($data);

        return WorkflowDefinition::fromArray($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validate(array $data): void
    {
        if (!isset($data['name']) || '' === $data['name']) {
            throw new InvalidWorkflowDefinitionException('Workflow definition must have a "name" field', 1711100005);
        }

        if (!isset($data['steps']) || [] === $data['steps']) {
            throw new InvalidWorkflowDefinitionException('Workflow definition must have at least one step', 1711100006);
        }

        foreach ($data['steps'] as $id => $step) {
            if (!isset($step['type']) || '' === $step['type']) {
                throw new InvalidWorkflowDefinitionException(sprintf('Step "%s" must have a "type" field', $id), 1711100007);
            }
        }
    }
}
