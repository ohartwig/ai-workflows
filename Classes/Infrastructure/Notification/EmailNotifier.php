<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Notification;

use Koh\AiWorkflows\Domain\Contract\NotifierInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class EmailNotifier implements NotifierInterface
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    public function getChannel(): string
    {
        return 'email';
    }

    /**
     * @param list<string>         $recipients
     * @param array<string, mixed> $context
     */
    public function notify(string $subject, string $message, array $recipients = [], array $context = []): void
    {
        if ([] === $recipients) {
            return;
        }

        try {
            $mail = GeneralUtility::makeInstance(MailMessage::class);
            $mail->subject($subject);
            $mail->text($message);

            foreach ($recipients as $recipient) {
                if (false === filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $mail->addTo(new Address($recipient));
            }

            $senderAddress = $context['senderAddress'] ?? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? '';
            $senderName = $context['senderName'] ?? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] ?? 'AI Workflows';

            if (is_string($senderAddress) && '' !== $senderAddress) {
                $mail->from(new Address($senderAddress, is_string($senderName) ? $senderName : 'AI Workflows'));
            }

            $this->mailer->send($mail);
        } catch (\Throwable $e) {
            $this->logger->error('Email notification failed', [
                'subject' => $subject,
                'recipientCount' => count($recipients),
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function supports(string $channel): bool
    {
        return 'email' === $channel;
    }
}
