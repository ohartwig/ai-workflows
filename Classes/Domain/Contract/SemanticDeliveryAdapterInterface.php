<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

interface SemanticDeliveryAdapterInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function transform(string $content, string $channel, array $options = []): string;
}
