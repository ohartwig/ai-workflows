<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Adapter;

use Koh\AiWorkflows\Domain\Contract\SemanticDeliveryAdapterInterface;

final class NullSemanticDeliveryAdapter implements SemanticDeliveryAdapterInterface
{
    public function transform(string $content, string $channel, array $options = []): string
    {
        return $content;
    }
}
