<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

interface ReviewTaskRepositoryInterface
{
    /**
     * @param int $uid
     *
     * @return ReviewTaskInterface|null
     */
    public function findByUid($uid);

    /**
     * @return list<ReviewTaskInterface>
     */
    public function findByStatus(string $status): array;

    /**
     * @return list<ReviewTaskInterface>
     */
    public function findByWorkflowInstance(int $workflowInstanceUid): array;

    /**
     * @return list<ReviewTaskInterface>
     */
    public function findOverdue(int $currentTimestamp): array;

    /**
     * @param ReviewTaskInterface $reviewTask
     *
     * @return void
     */
    public function add($reviewTask);

    /**
     * @param ReviewTaskInterface $reviewTask
     *
     * @return void
     */
    public function update($reviewTask);
}
