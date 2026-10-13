<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Persistence\Repository;

use Koh\AiWorkflows\Domain\Contract\WorkflowInstanceRepositoryInterface;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\WorkflowInstance;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<WorkflowInstance>
 */
class WorkflowInstanceRepository extends Repository implements WorkflowInstanceRepositoryInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->objectType = WorkflowInstance::class;
    }

    /**
     * @param int $uid
     *
     * @return WorkflowInstance|null
     */
    public function findByUid($uid)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('uid', $uid));
        /** @var WorkflowInstance|null $result */
        $result = $query->execute()->getFirst();

        return $result;
    }

    /**
     * @return list<WorkflowInstance>
     */
    public function findByStatus(string $status): array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('status', $status));
        /** @var list<WorkflowInstance> $results */
        $results = $query->execute()->toArray();

        return $results;
    }
}
