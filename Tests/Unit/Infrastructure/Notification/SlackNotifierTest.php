<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Notification;

use Koh\AiWorkflows\Infrastructure\Notification\SlackNotifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * The webhook URL for this notifier comes out of workflow YAML, which an editor
 * can change. That makes it an SSRF surface: the notifier would otherwise happily
 * POST to whatever host it is given, from inside the cluster.
 *
 * The guard is the point of these tests. `169.254.169.254` is the one that would
 * hurt — the cloud metadata endpoint hands out instance credentials to anything
 * that can reach it.
 *
 * All cases use literal IPs so `gethostbyname()` resolves without touching DNS.
 */
final class SlackNotifierTest extends TestCase
{
    private RequestFactory&MockObject $requestFactory;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = $this->createMock(RequestFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function notifier(): SlackNotifier
    {
        return new SlackNotifier($this->requestFactory, $this->logger);
    }

    #[Test]
    public function itAnnouncesTheSlackChannel(): void
    {
        self::assertSame('slack', $this->notifier()->getChannel());
        self::assertTrue($this->notifier()->supports('slack'));
        self::assertFalse($this->notifier()->supports('email'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function blockedUrls(): array
    {
        return [
            'cloud metadata endpoint' => ['https://169.254.169.254/latest/meta-data/'],
            'loopback' => ['https://127.0.0.1/hook'],
            'private class A' => ['https://10.0.0.1/hook'],
            'private class C' => ['https://192.168.1.1/hook'],
            'plain http' => ['http://1.1.1.1/hook'],
            'no scheme' => ['1.1.1.1/hook'],
            'not a url at all' => ['not-a-url'],
        ];
    }

    /**
     * The regression that matters most here.
     */
    #[Test]
    #[DataProvider('blockedUrls')]
    public function anUnsafeWebhookUrlIsNeverRequested(string $url): void
    {
        $this->requestFactory->expects(self::never())->method('request');
        $this->logger->expects(self::once())->method('warning');

        $this->notifier()->notify('Workflow failed', 'Step 2 broke', [], ['webhookUrl' => $url]);
    }

    #[Test]
    public function aPublicHttpsWebhookIsPosted(): void
    {
        $this->requestFactory->expects(self::once())
            ->method('request')
            ->with(
                'https://1.1.1.1/services/T000/B000/XXX',
                'POST',
                self::callback(static function (array $options): bool {
                    self::assertSame(10, $options['timeout'], 'a hanging webhook must not stall the workflow');
                    self::assertSame('*Workflow failed*\nStep 2 broke', $options['json']['text']);
                    self::assertArrayNotHasKey('channel', $options['json'], 'no recipient means Slack picks the default');

                    return true;
                }),
            );

        $this->notifier()->notify(
            'Workflow failed',
            'Step 2 broke',
            [],
            ['webhookUrl' => 'https://1.1.1.1/services/T000/B000/XXX'],
        );
    }

    /**
     * The first recipient overrides the webhook's default channel; the rest are
     * ignored, since a Slack webhook posts to exactly one place.
     */
    #[Test]
    public function theFirstRecipientBecomesTheTargetChannel(): void
    {
        $this->requestFactory->expects(self::once())
            ->method('request')
            ->with(
                self::anything(),
                'POST',
                self::callback(static function (array $options): bool {
                    self::assertSame('#alerts', $options['json']['channel']);

                    return true;
                }),
            );

        $this->notifier()->notify('Subject', 'Body', ['#alerts', '#ignored'], [
            'webhookUrl' => 'https://1.1.1.1/hook',
        ]);
    }

    /**
     * Without a configured webhook there is nothing to do, and nothing to warn
     * about — a workflow simply has no Slack notification set up.
     */
    #[Test]
    public function amissingWebhookUrlIsSilentlySkipped(): void
    {
        $this->requestFactory->expects(self::never())->method('request');
        $this->logger->expects(self::never())->method('warning');

        foreach ([[], ['webhookUrl' => ''], ['webhookUrl' => 123]] as $context) {
            $this->notifier()->notify('Subject', 'Body', [], $context);
        }
    }

    /**
     * A notification is a side channel. Slack being down must not take the
     * workflow with it — but it has to be logged, or the failure is invisible.
     */
    #[Test]
    public function aFailedRequestIsLoggedAndSwallowed(): void
    {
        $this->requestFactory->method('request')->willThrowException(new \RuntimeException('connect timeout'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Slack notification failed', self::callback(static function (array $context): bool {
                self::assertSame('connect timeout', $context['exception']);
                self::assertSame('Subject', $context['subject']);

                return true;
            }));

        $this->notifier()->notify('Subject', 'Body', [], ['webhookUrl' => 'https://1.1.1.1/hook']);
    }
}
