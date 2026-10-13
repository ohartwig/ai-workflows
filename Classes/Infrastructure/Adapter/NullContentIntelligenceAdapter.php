<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Adapter;

use Koh\AiWorkflows\Domain\Contract\ContentIntelligenceAdapterInterface;

final class NullContentIntelligenceAdapter implements ContentIntelligenceAdapterInterface
{
    public function analyzeContent(string $content, array $checks = []): array
    {
        return [
            'passed' => true,
            'score' => 1.0,
            'checks' => [],
            'message' => 'Content intelligence extension not installed — all checks passed by default',
        ];
    }
}
