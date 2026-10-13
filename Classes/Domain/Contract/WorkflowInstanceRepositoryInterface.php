<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

interface WorkflowInstanceRepositoryInterface
{
    /**
     * @param int $uid
     *
     * @return WorkflowInstanceInterface|null
     */
    public function findByUid($uid);

    /**
     * @return list<WorkflowInstanceInterface>
     */
    public function findByStatus(string $status): array;

    /**
     * @param object $workflowInstance
     *
     * @return void
     */
    public function add($workflowInstance);

    /**
     * @param object $workflowInstance
     *
     * @return void
     */
    public function update($workflowInstance);

    /**
     * @param object $workflowInstance
     *
     * @return void
     */
    public function remove($workflowInstance);
}
