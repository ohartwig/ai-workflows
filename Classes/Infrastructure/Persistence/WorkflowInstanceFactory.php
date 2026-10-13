<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Persistence;

use Koh\AiWorkflows\Domain\Contract\WorkflowInstanceFactoryInterface;
use Koh\AiWorkflows\Domain\Contract\WorkflowInstanceInterface;
use Koh\AiWorkflows\Infrastructure\Persistence\Model\WorkflowInstance;

final class WorkflowInstanceFactory implements WorkflowInstanceFactoryInterface
{
    public function create(): WorkflowInstanceInterface
    {
        return new WorkflowInstance();
    }
}
