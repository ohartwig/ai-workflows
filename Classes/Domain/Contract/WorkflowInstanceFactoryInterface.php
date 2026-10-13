<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

interface WorkflowInstanceFactoryInterface
{
    public function create(): WorkflowInstanceInterface;
}
