<?php

/*
 * SPDX-FileCopyrightText: 2026 Moselwal Digitalagentur <info@moselwal.de>
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

interface AiTaskRepositoryInterface
{
    /**
     * @param int $uid
     *
     * @return object|null
     */
    public function findByUid($uid);

    /**
     * @return list<object>
     */
    public function findPending(): array;

    public function findByWorkflowInstanceAndStep(int $workflowInstanceUid, string $stepId): ?object;

    /**
     * @param object $aiTask
     *
     * @return void
     */
    public function add($aiTask);

    /**
     * @param object $aiTask
     *
     * @return void
     */
    public function update($aiTask);
}
