<?php

/*
 * SPDX-FileCopyrightText: 2026 Moselwal Digitalagentur <info@moselwal.de>
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Koh\AiWorkflows\Application\Event;

final readonly class AiTaskCreatedEvent
{
    public function __construct(
        public int $workflowInstanceUid,
        public int $aiTaskUid,
        public string $taskType,
        public string $prompt,
    ) {}
}
