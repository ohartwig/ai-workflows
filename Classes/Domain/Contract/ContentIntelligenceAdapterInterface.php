<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

interface ContentIntelligenceAdapterInterface
{
    /**
     * @param array<string, mixed> $checks
     *
     * @return array<string, mixed>
     */
    public function analyzeContent(string $content, array $checks = []): array;
}
