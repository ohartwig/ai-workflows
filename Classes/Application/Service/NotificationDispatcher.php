<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Application\Service;

use Koh\AiWorkflows\Domain\Contract\NotifierInterface;

final class NotificationDispatcher
{
    /** @var NotifierInterface[] */
    private array $notifierList = [];

    /**
     * @param iterable<NotifierInterface> $notifiers
     */
    public function __construct(iterable $notifiers)
    {
        foreach ($notifiers as $notifier) {
            $this->notifierList[] = $notifier;
        }
    }

    /**
     * @param list<string>         $recipients
     * @param array<string, mixed> $context
     */
    public function dispatch(string $channel, string $subject, string $message, array $recipients = [], array $context = []): void
    {
        foreach ($this->notifierList as $notifier) {
            if ($notifier->supports($channel)) {
                $notifier->notify($subject, $message, $recipients, $context);
            }
        }
    }

    /**
     * @param list<string>         $recipients
     * @param array<string, mixed> $context
     */
    public function dispatchToAll(string $subject, string $message, array $recipients = [], array $context = []): void
    {
        foreach ($this->notifierList as $notifier) {
            $notifier->notify($subject, $message, $recipients, $context);
        }
    }
}
