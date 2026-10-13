<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Persistence\Repository;

use Koh\AiWorkflows\Domain\Contract\ReviewTaskRepositoryInterface;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\ReviewTask;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<ReviewTask>
 */
class ReviewTaskRepository extends Repository implements ReviewTaskRepositoryInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->objectType = ReviewTask::class;
    }

    /**
     * @param int $uid
     *
     * @return ReviewTask|null
     */
    public function findByUid($uid)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('uid', $uid));

        return $query->execute()->getFirst();
    }

    /**
     * @return list<ReviewTask>
     */
    public function findByStatus(string $status): array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('status', $status));

        return $query->execute()->toArray();
    }

    /**
     * @return list<ReviewTask>
     */
    public function findByWorkflowInstance(int $workflowInstanceUid): array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('workflowInstance', $workflowInstanceUid));

        return $query->execute()->toArray();
    }

    /**
     * @return list<ReviewTask>
     */
    public function findOverdue(int $currentTimestamp): array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalAnd(
                $query->equals('status', 'pending'),
                $query->lessThan('deadline', $currentTimestamp),
                $query->greaterThan('deadline', 0),
            ),
        );

        return $query->execute()->toArray();
    }
}
