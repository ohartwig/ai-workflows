<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Notification;

use Koh\AiWorkflows\Domain\Contract\NotifierInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class BackendNotifier implements NotifierInterface
{
    public function getChannel(): string
    {
        return 'backend';
    }

    /**
     * @param list<string>         $recipients
     * @param array<string, mixed> $context
     */
    public function notify(string $subject, string $message, array $recipients = [], array $context = []): void
    {
        $severity = match ($context['severity'] ?? 'info') {
            'error' => ContextualFeedbackSeverity::ERROR,
            'warning' => ContextualFeedbackSeverity::WARNING,
            'success' => ContextualFeedbackSeverity::OK,
            default => ContextualFeedbackSeverity::INFO,
        };

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            htmlspecialchars($message),
            htmlspecialchars($subject),
            $severity,
            true,
        );

        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $flashMessageService->getMessageQueueByIdentifier('aiworkflows')->addMessage($flashMessage);
    }

    public function supports(string $channel): bool
    {
        return 'backend' === $channel;
    }
}
