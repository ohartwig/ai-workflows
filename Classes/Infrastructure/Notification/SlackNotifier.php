<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Notification;

use Koh\AiWorkflows\Domain\Contract\NotifierInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

final class SlackNotifier implements NotifierInterface
{
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly LoggerInterface $logger,
    ) {}

    public function getChannel(): string
    {
        return 'slack';
    }

    /**
     * @param list<string>         $recipients
     * @param array<string, mixed> $context
     */
    public function notify(string $subject, string $message, array $recipients = [], array $context = []): void
    {
        $webhookUrl = $context['webhookUrl'] ?? '';
        if (!is_string($webhookUrl) || '' === $webhookUrl) {
            return;
        }

        if (!$this->isUrlAllowed($webhookUrl)) {
            $this->logger->warning('Slack webhook URL blocked by SSRF protection', [
                'subject' => $subject,
            ]);

            return;
        }

        $payload = [
            'text' => sprintf('*%s*\n%s', $subject, $message),
        ];

        if ([] !== $recipients) {
            $payload['channel'] = $recipients[0];
        }

        try {
            $this->requestFactory->request($webhookUrl, 'POST', [
                'json' => $payload,
                'timeout' => 10,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Slack notification failed', [
                'subject' => $subject,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function supports(string $channel): bool
    {
        return 'slack' === $channel;
    }

    private function isUrlAllowed(string $url): bool
    {
        $parsed = parse_url($url);
        if (false === $parsed || !isset($parsed['scheme'], $parsed['host'])) {
            return false;
        }

        if (!in_array($parsed['scheme'], ['https'], true)) {
            return false;
        }

        $host = $parsed['host'];
        $ip = gethostbyname($host);

        return false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
