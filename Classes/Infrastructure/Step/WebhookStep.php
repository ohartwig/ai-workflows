<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use TYPO3\CMS\Core\Http\RequestFactory;

final class WebhookStep extends AbstractStep
{
    private const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    private const REQUEST_TIMEOUT = 30;

    public function __construct(
        private readonly RequestFactory $requestFactory,
    ) {}

    public function getType(): string
    {
        return 'webhook';
    }

    public function execute(StepDefinition $definition, WorkflowContext $context): StepResult
    {
        $url = (string) $this->requireParameter($definition, 'url');
        $method = strtoupper((string) $this->getParameter($definition, 'method', 'POST'));
        $headers = (array) $this->getParameter($definition, 'headers', []);
        $body = (array) $this->getParameter($definition, 'body', []);

        if (!in_array($method, self::ALLOWED_METHODS, true)) {
            return StepResult::error(sprintf('HTTP method "%s" is not allowed', $method));
        }

        $resolvedIp = $this->resolveAllowedUrl($url);
        if (null === $resolvedIp) {
            return StepResult::error('URL is not allowed: blocked by security policy');
        }

        // SECURITY (audit M15): the previous implementation rewrote the URL
        // host to the resolved IP and set a Host header. For HTTPS that
        // breaks SNI and certificate validation — the TLS handshake sees
        // the IP as the SNI value and the cert chain check fails (or
        // worse, an operator disables verify_peer to make it work). Use
        // curl's CURLOPT_RESOLVE instead: the URL stays intact (SNI +
        // cert validation see the real hostname), but the actual TCP
        // socket targets the IP we pre-validated, closing the DNS-
        // rebinding TOCTOU window between resolveAllowedUrl() and the
        // real fetch.
        $parsed = parse_url($url);
        /** @var array{scheme: string, host: string, port?: int, path?: string, query?: string} $parsed */
        $host = $parsed['host'];
        $port = $parsed['port'] ?? ('https' === $parsed['scheme'] ? 443 : 80);

        $options = [
            'headers' => $headers,
            'timeout' => self::REQUEST_TIMEOUT,
            'curl' => [
                CURLOPT_RESOLVE => [sprintf('%s:%d:%s', $host, $port, $resolvedIp)],
            ],
        ];
        if ([] !== $body && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $options['json'] = $body;
        }

        try {
            $response = $this->requestFactory->request($url, $method, $options);
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            if ($statusCode >= 200 && $statusCode < 300) {
                $decoded = json_decode($responseBody, true);

                return StepResult::success([
                    'statusCode' => $statusCode,
                    'body' => is_array($decoded) ? $decoded : $responseBody,
                ]);
            }

            return StepResult::error(
                sprintf('Webhook returned status %d', $statusCode),
                ['statusCode' => $statusCode, 'body' => $responseBody],
            );
        } catch (\Throwable $e) {
            return StepResult::error(sprintf('Webhook failed: %s', $e->getMessage()));
        }
    }

    /**
     * Validates the URL and resolves it to a safe IP to prevent DNS rebinding (TOCTOU mitigation).
     *
     * @return string|null The resolved IP address if the URL is allowed, null otherwise
     */
    private function resolveAllowedUrl(string $url): ?string
    {
        $parsed = parse_url($url);
        if (false === $parsed || !isset($parsed['scheme'], $parsed['host'])) {
            return null;
        }

        if (!in_array($parsed['scheme'], ['http', 'https'], true)) {
            return null;
        }

        $host = $parsed['host'];

        // SECURITY (audit M15 / H1 parallel): gethostbyname returns only the
        // first A record — if the host has both public and private A records,
        // a single-lookup picks one at random and either accidentally lets a
        // private one through (when the public one was checked) or rejects a
        // legitimate request. gethostbynamel walks all A-records; we reject
        // when ANY resolves into a non-public range.
        if (false !== filter_var($host, FILTER_VALIDATE_IP)) {
            // Literal IP in the URL: validate it directly. gethostbynamel
            // wouldn't help here.
            if (false === filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return null;
            }

            return $host;
        }

        // gethostbynamel() answers false rather than null when a host does not
        // resolve, so ?? would not catch it and ?: is disallowed by strict rules.
        $resolved = gethostbynamel($host);
        $ipv4s = false === $resolved ? [] : $resolved;
        if ([] === $ipv4s) {
            return null;
        }
        foreach ($ipv4s as $ip) {
            if (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return null;
            }
        }

        // All A-records are public — pin to the first one for the upcoming
        // CURLOPT_RESOLVE call. The TLS handshake will still verify against
        // the original hostname (SNI), so cert chains work normally.
        return $ipv4s[0];
    }

    public function validate(StepDefinition $definition): array
    {
        $errors = [];
        if (!isset($definition->parameters['url'])) {
            $errors[] = 'Parameter "url" is required';
        }

        return $errors;
    }
}
