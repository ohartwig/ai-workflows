<?php

/*
 * SPDX-FileCopyrightText: 2026 Moselwal Digitalagentur <info@moselwal.de>
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Persistence\Repository;

use Koh\AiWorkflows\Domain\Contract\AiTaskRepositoryInterface;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\AiTask;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<AiTask>
 */
class AiTaskRepository extends Repository implements AiTaskRepositoryInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->objectType = AiTask::class;
    }

    /**
     * @param int $uid
     *
     * @return AiTask|null
     */
    public function findByUid($uid)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('uid', $uid));

        return $query->execute()->getFirst();
    }

    /**
     * @return list<AiTask>
     */
    public function findPending(): array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('status', 'pending'));

        return $query->execute()->toArray();
    }

    public function findByWorkflowInstanceAndStep(int $workflowInstanceUid, string $stepId): ?AiTask
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalAnd(
                $query->equals('workflowInstance', $workflowInstanceUid),
                $query->equals('stepId', $stepId),
            ),
        );

        return $query->execute()->getFirst();
    }
}
