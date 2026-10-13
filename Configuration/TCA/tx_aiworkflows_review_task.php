<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_review_task',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,status',
        'iconfile' => 'EXT:ai_workflows/Resources/Public/Icons/review-task.svg',
    ],
    'types' => [
        '1' => [
            'showitem' => 'title, description, workflow_instance, step_id, status, reviewer, deadline, reviewed_at, review_comment,
                --div--;Content, content_data',
        ],
    ],
    'columns' => [
        'workflow_instance' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_review_task.workflow_instance',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_aiworkflows_workflow_instance',
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'step_id' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_review_task.step_id',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'title' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_review_task.title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_review_task.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 80,
            ],
        ],
        'content_data' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_review_task.content_data',
            'config' => [
                'type' => 'text',
                'rows' => 15,
                'cols' => 80,
            ],
        ],
        'status' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_review_task.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Pending', 'value' => 'pending'],
                    ['label' => 'Approved', 'value' => 'approved'],
                    ['label' => 'Rejected', 'value' => 'rejected'],
                    ['label' => 'Timeout', 'value' => 'timeout'],
                ],
                'default' => 'pending',
            ],
        ],
        'reviewer' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_review_task.reviewer',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users',
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'review_comment' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_review_task.review_comment',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 80,
            ],
        ],
        'deadline' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_review_task.deadline',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'reviewed_at' => [
            'label' => 'LLL:EXT:ai_workflows/Resources/Private/Language/locallang.xlf:tx_aiworkflows_review_task.reviewed_at',
            'config' => [
                'type' => 'datetime',
            ],
        ],
    ],
];
