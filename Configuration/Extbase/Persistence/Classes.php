<?php

declare(strict_types=1);

return [
    Koh\AiWorkflows\Infrastructure\Persistence\Model\WorkflowInstance::class => [
        'tableName' => 'tx_aiworkflows_workflow_instance',
    ],
    Koh\AiWorkflows\Infrastructure\Persistence\Model\ReviewTask::class => [
        'tableName' => 'tx_aiworkflows_review_task',
    ],
    Koh\AiWorkflows\Infrastructure\Persistence\Model\AiTask::class => [
        'tableName' => 'tx_aiworkflows_ai_task',
    ],
];
