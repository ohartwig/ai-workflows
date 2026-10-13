<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Adapter;

use Koh\AiWorkflows\Domain\Contract\AiClientInterface;

final class NullAiClient implements AiClientInterface
{
    public function generate(string $prompt, array $options = []): string
    {
        return sprintf('[AI Client not configured] Prompt received: %s', mb_substr($prompt, 0, 100));
    }
}
