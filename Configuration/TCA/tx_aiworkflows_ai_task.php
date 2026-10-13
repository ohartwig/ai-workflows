<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'AI Task',
        'label' => 'step_id',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'step_id,status,task_type',
        'hideTable' => true,
        'iconfile' => 'EXT:ai_workflows/Resources/Public/Icons/Extension.svg',
    ],
    'types' => [
        '1' => [
            'showitem' => 'step_id, task_type, status, workflow_instance, prompt, context_data, expected_schema, response_data, created_at, completed_at, completed_by',
        ],
    ],
    'columns' => [
        'workflow_instance' => [
            'label' => 'Workflow Instance',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_aiworkflows_workflow_instance',
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'step_id' => [
            'label' => 'Step ID',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'task_type' => [
            'label' => 'Task Type',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 100,
                'eval' => 'trim',
            ],
        ],
        'prompt' => [
            'label' => 'Prompt',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'cols' => 80,
            ],
        ],
        'context_data' => [
            'label' => 'Context Data',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'cols' => 80,
            ],
        ],
        'expected_schema' => [
            'label' => 'Expected Schema',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'cols' => 80,
            ],
        ],
        'response_data' => [
            'label' => 'Response Data',
            'config' => [
                'type' => 'text',
                'rows' => 15,
                'cols' => 80,
            ],
        ],
        'status' => [
            'label' => 'Status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Pending', 'value' => 'pending'],
                    ['label' => 'Processing', 'value' => 'processing'],
                    ['label' => 'Completed', 'value' => 'completed'],
                    ['label' => 'Failed', 'value' => 'failed'],
                ],
                'default' => 'pending',
            ],
        ],
        'created_at' => [
            'label' => 'Created At',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'completed_at' => [
            'label' => 'Completed At',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'completed_by' => [
            'label' => 'Completed By',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
    ],
];
