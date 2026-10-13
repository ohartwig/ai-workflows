<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Adapter;

use Koh\AiWorkflows\Infrastructure\Adapter\NullAiClient;
use Koh\AiWorkflows\Infrastructure\Adapter\NullContentIntelligenceAdapter;
use Koh\AiWorkflows\Infrastructure\Adapter\NullMcpServerAdapter;
use Koh\AiWorkflows\Infrastructure\Adapter\NullSemanticDeliveryAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NullAdaptersTest extends TestCase
{
    #[Test]
    public function nullAiClientReturnsPlaceholder(): void
    {
        $result = new NullAiClient()->generate('Write about AI');
        self::assertStringContainsString('not configured', $result);
        self::assertStringContainsString('Write about AI', $result);
    }

    #[Test]
    public function nullContentIntelligencePassesAll(): void
    {
        $result = new NullContentIntelligenceAdapter()->analyzeContent('content', ['readability']);
        self::assertTrue($result['passed']);
        self::assertSame(1.0, $result['score']);
    }

    #[Test]
    public function nullSemanticDeliveryReturnsUnchanged(): void
    {
        self::assertSame('My content', new NullSemanticDeliveryAdapter()->transform('My content', 'web'));
    }

    #[Test]
    public function nullMcpServerReturnsError(): void
    {
        $result = new NullMcpServerAdapter()->executeOperation('op', ['arg' => 1]);
        self::assertFalse($result['success']);
        self::assertStringContainsString('not installed', $result['error']);
    }
}
