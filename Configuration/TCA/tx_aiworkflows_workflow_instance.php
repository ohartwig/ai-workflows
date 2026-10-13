<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance',
        'label' => 'workflow_name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'workflow_name,status',
        'iconfile' => 'EXT:ai_workflows/Resources/Public/Icons/workflow-instance.svg',
    ],
    'types' => [
        '1' => [
            'showitem' => 'workflow_name, status, current_step, started_by, started_at, completed_at, error_message,
                --div--;Data, workflow_definition, context_data, input_data, output_data',
        ],
    ],
    'columns' => [
        'workflow_name' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance.workflow_name',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'workflow_definition' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance.workflow_definition',
            'config' => [
                'type' => 'text',
                'rows' => 15,
                'cols' => 80,
            ],
        ],
        'status' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Pending', 'value' => 'pending'],
                    ['label' => 'Running', 'value' => 'running'],
                    ['label' => 'Paused', 'value' => 'paused'],
                    ['label' => 'Completed', 'value' => 'completed'],
                    ['label' => 'Failed', 'value' => 'failed'],
                    ['label' => 'Cancelled', 'value' => 'cancelled'],
                ],
                'default' => 'pending',
            ],
        ],
        'current_step' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance.current_step',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'context_data' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance.context_data',
            'config' => [
                'type' => 'text',
                'rows' => 15,
                'cols' => 80,
            ],
        ],
        'input_data' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance.input_data',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'cols' => 80,
            ],
        ],
        'output_data' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance.output_data',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'cols' => 80,
            ],
        ],
        'started_by' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance.started_by',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users',
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'started_at' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance.started_at',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'completed_at' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance.completed_at',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'error_message' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_workflow_instance.error_message',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 80,
            ],
        ],
    ],
];
