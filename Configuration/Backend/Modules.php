<?php

declare(strict_types=1);

use Koh\AiWorkflows\Presentation\Controller\ReviewController;
use Koh\AiWorkflows\Presentation\Controller\WorkflowDashboardController;

return [
    'aiworkflows_dashboard' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'iconIdentifier' => 'module-aiworkflows',
        'path' => '/module/web/aiworkflows-dashboard',
        'labels' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'AiWorkflows',
        'controllerActions' => [
            WorkflowDashboardController::class => ['index', 'show', 'start', 'execute'],
        ],
    ],
    'aiworkflows_review' => [
        'parent' => 'web',
        'position' => ['after' => 'aiworkflows_dashboard'],
        'access' => 'user',
        'iconIdentifier' => 'module-aiworkflows-review',
        'path' => '/module/web/aiworkflows-review',
        'labels' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang_mod_review.xlf',
        'extensionName' => 'AiWorkflows',
        'controllerActions' => [
            ReviewController::class => ['index', 'show', 'approve', 'reject'],
        ],
    ],
];
