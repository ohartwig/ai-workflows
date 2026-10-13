<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Notification;

use Koh\AiWorkflows\Infrastructure\Notification\EmailNotifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\RawMessage;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Recipient addresses reach this notifier from workflow YAML, so a typo is a
 * matter of when rather than if. A single bad address must not cost the whole
 * notification — the operators with valid addresses still need to hear about the
 * workflow.
 *
 * The sender is the other half: TYPO3 configures a default, the workflow may
 * override it, and if neither exists the mail must go out without a From rather
 * than with an empty one.
 */
final class EmailNotifierTest extends TestCase
{
    private Mailer&MockObject $mailer;
    private LoggerInterface&MockObject $logger;

    /** @var list<MailMessage> */
    private array $sentMails = [];

    /** @var array<string, mixed> */
    private array $mailConfBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->sentMails = [];
        $this->mailConfBackup = $GLOBALS['TYPO3_CONF_VARS']['MAIL'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = [];

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mailer = $this->createMock(Mailer::class);
        $this->mailer->method('send')->willReturnCallback(
            function (RawMessage $message): void {
                self::assertInstanceOf(MailMessage::class, $message);
                $this->sentMails[] = $message;
            },
        );
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = $this->mailConfBackup;
        GeneralUtility::purgeInstances();

        parent::tearDown();
    }

    /**
     * The notifier builds its message through makeInstance(), so a real one is
     * queued for it to pick up. addInstance() is a one-shot queue — every
     * notify() call needs its own.
     */
    private function expectOneMailToBeBuilt(): void
    {
        GeneralUtility::addInstance(MailMessage::class, new MailMessage());
    }

    private function notifier(): EmailNotifier
    {
        return new EmailNotifier($this->mailer, $this->logger);
    }

    private function sentMail(): MailMessage
    {
        self::assertCount(1, $this->sentMails, 'exactly one mail must have reached the mailer');

        return $this->sentMails[0];
    }

    #[Test]
    public function itAnnouncesTheEmailChannel(): void
    {
        self::assertSame('email', $this->notifier()->getChannel());
        self::assertTrue($this->notifier()->supports('email'));
        self::assertFalse($this->notifier()->supports('slack'));
    }

    /**
     * Nobody to notify means nothing to send — and no mail object built either.
     */
    #[Test]
    public function withoutRecipientsNothingIsSent(): void
    {
        $this->mailer->expects(self::never())->method('send');

        $this->notifier()->notify('Workflow finished', 'All steps completed');
    }

    #[Test]
    public function subjectAndBodyReachTheMail(): void
    {
        $this->expectOneMailToBeBuilt();

        $this->notifier()->notify('Workflow finished', 'All steps completed', ['ops@example.org']);

        $mail = $this->sentMail();
        self::assertSame('Workflow finished', $mail->getSubject());
        self::assertSame('All steps completed', $mail->getTextBody());
        self::assertSame('ops@example.org', $mail->getTo()[0]->getAddress());
    }

    /**
     * The regression worth guarding: one malformed address in the list must not
     * silence the notification for everyone else.
     */
    #[Test]
    public function anInvalidAddressIsSkippedWhileTheValidOnesRemain(): void
    {
        $this->expectOneMailToBeBuilt();

        $this->notifier()->notify('Subject', 'Body', [
            'ops@example.org',
            'not-an-address',
            'lead@example.org',
        ]);

        self::assertSame(
            ['ops@example.org', 'lead@example.org'],
            \array_map(
                static fn(\Symfony\Component\Mime\Address $address): string => $address->getAddress(),
                $this->sentMail()->getTo(),
            ),
        );
    }

    #[Test]
    public function theSenderFromTheWorkflowContextWins(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = [
            'defaultMailFromAddress' => 'typo3@example.com',
            'defaultMailFromName' => 'TYPO3',
        ];
        $this->expectOneMailToBeBuilt();

        $this->notifier()->notify('Subject', 'Body', ['ops@example.org'], [
            'senderAddress' => 'workflows@example.com',
            'senderName' => 'AI Workflows',
        ]);

        $from = $this->sentMail()->getFrom()[0];
        self::assertSame('workflows@example.com', $from->getAddress());
        self::assertSame('AI Workflows', $from->getName());
    }

    #[Test]
    public function theInstallationDefaultIsUsedWhenTheWorkflowNamesNoSender(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = [
            'defaultMailFromAddress' => 'typo3@example.com',
            'defaultMailFromName' => 'TYPO3 Installation',
        ];
        $this->expectOneMailToBeBuilt();

        $this->notifier()->notify('Subject', 'Body', ['ops@example.org']);

        $from = $this->sentMail()->getFrom()[0];
        self::assertSame('typo3@example.com', $from->getAddress());
        self::assertSame('TYPO3 Installation', $from->getName());
    }

    /**
     * An unnamed sender still needs a display name, or the mail shows a bare
     * address. The fallback is the extension's own name.
     */
    #[Test]
    public function anUnnamedSenderFallsBackToTheExtensionName(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = ['defaultMailFromAddress' => 'typo3@example.com'];
        $this->expectOneMailToBeBuilt();

        $this->notifier()->notify('Subject', 'Body', ['ops@example.org']);

        self::assertSame('AI Workflows', $this->sentMail()->getFrom()[0]->getName());
    }

    /**
     * With no sender configured anywhere the mail goes out without a From and the
     * MTA fills one in. Inventing an empty address here would make it bounce.
     */
    #[Test]
    public function noConfiguredSenderLeavesTheFromUnset(): void
    {
        $this->expectOneMailToBeBuilt();

        $this->notifier()->notify('Subject', 'Body', ['ops@example.org']);

        self::assertSame([], $this->sentMail()->getFrom());
    }

    /**
     * A notification is a side channel: a dead mail relay must not fail the
     * workflow, but it has to be logged or the silence is invisible.
     */
    #[Test]
    public function aMailerFailureIsLoggedAndSwallowed(): void
    {
        $mailer = $this->createMock(Mailer::class);
        $mailer->method('send')->willThrowException(new \RuntimeException('relay refused'));

        GeneralUtility::addInstance(MailMessage::class, new MailMessage());

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Email notification failed', self::callback(static function (array $context): bool {
                self::assertSame('relay refused', $context['exception']);
                self::assertSame(2, $context['recipientCount']);

                return true;
            }));

        new EmailNotifier($mailer, $this->logger)
            ->notify('Subject', 'Body', ['ops@example.org', 'lead@example.org']);
    }
}
