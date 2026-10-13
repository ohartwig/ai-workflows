<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

interface AiClientInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function generate(string $prompt, array $options = []): string;
}
