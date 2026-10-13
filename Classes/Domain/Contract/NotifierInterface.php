<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Domain\Contract;

interface NotifierInterface
{
    public function getChannel(): string;

    /**
     * @param list<string>         $recipients
     * @param array<string, mixed> $context
     */
    public function notify(string $subject, string $message, array $recipients = [], array $context = []): void;

    public function supports(string $channel): bool;
}
