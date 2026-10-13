<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Step;

use Koh\AiWorkflows\Domain\Model\StepDefinition;
use Koh\AiWorkflows\Domain\Model\StepResult;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Infrastructure\Step\WebhookStep;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

final class WebhookStepTest extends TestCase
{
    #[Test]
    public function typeIsWebhook(): void
    {
        self::assertSame('webhook', new WebhookStep($this->createMock(RequestFactory::class))->getType());
        self::assertFalse(new WebhookStep($this->createMock(RequestFactory::class))->isBlocking());
    }

    #[Test]
    public function rejectsDisallowedHttpMethod(): void
    {
        $r = new WebhookStep($this->createMock(RequestFactory::class))->execute(new StepDefinition('w1', 'webhook', ['url' => 'https://example.com', 'method' => 'TRACE']), new WorkflowContext());
        self::assertFalse($r->isSuccessful());
        self::assertStringContainsString('not allowed', $r->message);
    }

    #[Test]
    public function rejectsInvalidUrlScheme(): void
    {
        $r = new WebhookStep($this->createMock(RequestFactory::class))->execute(new StepDefinition('w1', 'webhook', ['url' => 'ftp://example.com/file']), new WorkflowContext());
        self::assertFalse($r->isSuccessful());
        self::assertStringContainsString('security policy', $r->message);
    }

    #[Test]
    public function rejectsMalformedUrl(): void
    {
        $r = new WebhookStep($this->createMock(RequestFactory::class))->execute(new StepDefinition('w1', 'webhook', ['url' => 'not-a-url']), new WorkflowContext());
        self::assertFalse($r->isSuccessful());
    }

    #[Test]
    public function validateRequiresUrl(): void
    {
        self::assertNotEmpty(new WebhookStep($this->createMock(RequestFactory::class))->validate(new StepDefinition('w1', 'webhook', [])));
    }

    /**
     * A webhook URL comes out of workflow YAML, so it is editor-controlled and
     * the request leaves from inside the cluster. Literal IPs are used
     * throughout so the checks run without touching DNS.
     *
     * @return array<string, array{string}>
     */
    public static function blockedTargets(): array
    {
        return [
            'cloud metadata endpoint' => ['https://169.254.169.254/latest/meta-data/'],
            'loopback' => ['http://127.0.0.1/hook'],
            'private class A' => ['https://10.0.0.1/hook'],
            'private class B' => ['https://172.16.0.1/hook'],
            'private class C' => ['http://192.168.1.1/hook'],
        ];
    }

    #[Test]
    #[DataProvider('blockedTargets')]
    public function anInternalTargetIsNeverRequested(string $url): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects(self::never())->method('request');

        $result = new WebhookStep($requestFactory)->execute(
            new StepDefinition('w1', 'webhook', ['url' => $url]),
            new WorkflowContext(),
        );

        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('security policy', $result->message);
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $captured
     */
    private function runAgainst(
        int $statusCode,
        string $body,
        array $parameters = [],
        ?array &$captured = null,
        ?\Throwable $failWith = null,
    ): StepResult {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($body);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($stream);

        $requestFactory = $this->createMock(RequestFactory::class);
        if (null !== $failWith) {
            $requestFactory->method('request')->willThrowException($failWith);
        } else {
            $requestFactory->method('request')->willReturnCallback(
                static function (string $url, string $method, array $options) use ($response, &$captured): ResponseInterface {
                    $captured = ['url' => $url, 'method' => $method, 'options' => $options];

                    return $response;
                },
            );
        }

        return new WebhookStep($requestFactory)->execute(
            new StepDefinition('w1', 'webhook', \array_merge(['url' => 'https://1.1.1.1/hook'], $parameters)),
            new WorkflowContext(),
        );
    }

    #[Test]
    public function aJsonResponseIsDecodedIntoTheStepOutput(): void
    {
        $result = $this->runAgainst(200, '{"id":"abc","ok":true}');

        self::assertTrue($result->isSuccessful());
        self::assertSame(200, $result->output['statusCode']);
        self::assertSame(['id' => 'abc', 'ok' => true], $result->output['body']);
    }

    /**
     * Not every endpoint answers JSON. A plain-text body has to survive as text
     * rather than collapsing to null and looking like an empty response.
     */
    #[Test]
    public function aNonJsonResponseIsKeptAsText(): void
    {
        $result = $this->runAgainst(201, 'accepted');

        self::assertTrue($result->isSuccessful());
        self::assertSame('accepted', $result->output['body']);
    }

    #[Test]
    public function anErrorStatusFailsTheStepAndKeepsTheBody(): void
    {
        $result = $this->runAgainst(500, 'upstream exploded');

        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('status 500', $result->message);
        self::assertSame(500, $result->output['statusCode']);
        self::assertSame('upstream exploded', $result->output['body'], 'the body is what makes the failure diagnosable');
    }

    /**
     * A redirect is not a success. Treating 3xx as delivered would report a
     * webhook as fired when it only got as far as a Location header.
     */
    #[Test]
    public function aRedirectIsNotTreatedAsSuccess(): void
    {
        self::assertFalse($this->runAgainst(302, '')->isSuccessful());
    }

    #[Test]
    public function aTransportFailureIsReportedRatherThanThrown(): void
    {
        $result = $this->runAgainst(200, '', failWith: new \RuntimeException('connect timeout'));

        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('connect timeout', $result->message);
    }

    /**
     * The DNS-rebinding mitigation. The URL keeps its hostname so TLS still
     * validates against the real certificate, while the socket is pinned to the
     * address that was checked a moment earlier — closing the window between
     * the check and the fetch.
     */
    #[Test]
    public function theRequestIsPinnedToThePreValidatedAddress(): void
    {
        $captured = null;
        $this->runAgainst(200, '{}', captured: $captured);

        self::assertSame('https://1.1.1.1/hook', $captured['url'], 'the hostname must survive for SNI');
        self::assertSame(['1.1.1.1:443:1.1.1.1'], $captured['options']['curl'][\CURLOPT_RESOLVE]);
        self::assertSame(30, $captured['options']['timeout'], 'a hanging webhook must not stall the workflow');
    }

    #[Test]
    public function headersArePassedThrough(): void
    {
        $captured = null;
        $this->runAgainst(200, '{}', ['headers' => ['X-Token' => 'secret']], $captured);

        self::assertSame(['X-Token' => 'secret'], $captured['options']['headers']);
    }

    /**
     * A body belongs on the verbs that carry one. Attaching it to GET or DELETE
     * makes some servers reject the request outright.
     */
    #[Test]
    public function aBodyIsOnlySentWithVerbsThatCarryOne(): void
    {
        foreach (['POST', 'PUT', 'PATCH'] as $method) {
            $captured = null;
            $this->runAgainst(200, '{}', ['method' => $method, 'body' => ['a' => 1]], $captured);
            self::assertSame(['a' => 1], $captured['options']['json'] ?? null, $method);
        }

        foreach (['GET', 'DELETE'] as $method) {
            $captured = null;
            $this->runAgainst(200, '{}', ['method' => $method, 'body' => ['a' => 1]], $captured);
            self::assertArrayNotHasKey('json', $captured['options'], $method);
        }
    }

    #[Test]
    public function theMethodIsNormalisedToUppercase(): void
    {
        $captured = null;
        $this->runAgainst(200, '{}', ['method' => 'post'], $captured);

        self::assertSame('POST', $captured['method']);
    }

    #[Test]
    public function postIsTheDefaultMethod(): void
    {
        $captured = null;
        $this->runAgainst(200, '{}', captured: $captured);

        self::assertSame('POST', $captured['method']);
    }
}
