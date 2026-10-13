<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Notification;

use Koh\AiWorkflows\Infrastructure\Notification\BackendNotifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A workflow notification ends up in the backend as a flash message. Two things
 * matter: the severity, because an editor reads a red box differently from a blue
 * one, and the escaping — the message text can carry AI-generated content, and it
 * is rendered as markup.
 */
final class BackendNotifierTest extends TestCase
{
    /** @var list<FlashMessage> */
    private array $queued = [];

    private string $queueIdentifier = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->queued = [];
        $this->queueIdentifier = '';

        $queue = $this->createMock(FlashMessageQueue::class);
        $queue->method('addMessage')->willReturnCallback(
            function (FlashMessage $message): void {
                $this->queued[] = $message;
            },
        );

        $service = $this->createMock(FlashMessageService::class);
        $service->method('getMessageQueueByIdentifier')->willReturnCallback(
            function (string $identifier) use ($queue): FlashMessageQueue {
                $this->queueIdentifier = $identifier;

                return $queue;
            },
        );

        // FlashMessageService is a singleton, so it has to be injected as one.
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $service);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();

        parent::tearDown();
    }

    private function queuedMessage(): FlashMessage
    {
        self::assertCount(1, $this->queued, 'exactly one flash message must have been queued');

        return $this->queued[0];
    }

    #[Test]
    public function itAnnouncesTheBackendChannel(): void
    {
        $notifier = new BackendNotifier();

        self::assertSame('backend', $notifier->getChannel());
        self::assertTrue($notifier->supports('backend'));
        self::assertFalse($notifier->supports('slack'));
    }

    /**
     * The queue identifier is what the backend module reads its messages from.
     * A different one here means the notification is stored but never shown.
     */
    #[Test]
    public function theMessageGoesIntoTheExtensionsOwnQueue(): void
    {
        new BackendNotifier()->notify('Workflow finished', 'All steps completed');

        self::assertSame('aiworkflows', $this->queueIdentifier);

        $message = $this->queuedMessage();
        self::assertSame('Workflow finished', $message->getTitle());
        self::assertSame('All steps completed', $message->getMessage());
    }

    /**
     * @return array<string, array{string, ContextualFeedbackSeverity}>
     */
    public static function severities(): array
    {
        return [
            'error' => ['error', ContextualFeedbackSeverity::ERROR],
            'warning' => ['warning', ContextualFeedbackSeverity::WARNING],
            'success' => ['success', ContextualFeedbackSeverity::OK],
            'info' => ['info', ContextualFeedbackSeverity::INFO],
            'an unknown severity is not an error' => ['something-else', ContextualFeedbackSeverity::INFO],
        ];
    }

    #[Test]
    #[DataProvider('severities')]
    public function theSeverityIsMappedToTheBackendsOwnScale(string $given, ContextualFeedbackSeverity $expected): void
    {
        new BackendNotifier()->notify('Subject', 'Body', [], ['severity' => $given]);

        self::assertSame($expected, $this->queuedMessage()->getSeverity());
    }

    #[Test]
    public function withoutAnyContextTheMessageIsInformational(): void
    {
        new BackendNotifier()->notify('Subject', 'Body');

        self::assertSame(ContextualFeedbackSeverity::INFO, $this->queuedMessage()->getSeverity());
    }

    /**
     * The regression worth guarding: workflow output can contain AI-generated
     * text, and a flash message is rendered as markup. Unescaped, a step result
     * carrying a tag would execute in the backend.
     */
    #[Test]
    public function markupInTheNotificationIsEscaped(): void
    {
        new BackendNotifier()->notify(
            'Result <b>ready</b>',
            '<script>alert("xss")</script> & done',
        );

        $message = $this->queuedMessage();
        self::assertSame('Result &lt;b&gt;ready&lt;/b&gt;', $message->getTitle());
        self::assertStringNotContainsString('<script>', $message->getMessage());
        self::assertStringContainsString('&amp; done', $message->getMessage());
    }

    /**
     * Flash messages survive a redirect only if they are stored in the session;
     * a workflow notification is raised in one request and read in the next.
     */
    #[Test]
    public function theMessageIsStoredInTheSession(): void
    {
        new BackendNotifier()->notify('Subject', 'Body');

        self::assertTrue($this->queuedMessage()->isSessionMessage());
    }
}
