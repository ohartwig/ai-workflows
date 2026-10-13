<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Presentation\EventListener;

use Koh\AiWorkflows\Application\Event\ReviewRequestedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowCompletedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowFailedEvent;
use Koh\AiWorkflows\Application\Event\WorkflowStartedEvent;
use Koh\AiWorkflows\Application\Service\NotificationDispatcher;
use Koh\AiWorkflows\Domain\Contract\NotifierInterface;
use Koh\AiWorkflows\Domain\Model\WorkflowContext;
use Koh\AiWorkflows\Presentation\EventListener\WorkflowNotificationListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * These four notifications are the only thing an operator sees of a workflow
 * that is not being watched. The severity is what carries the meaning — a failed
 * workflow arriving as a blue info box is the same as not being told at all —
 * and the instance number is what makes a message actionable rather than a
 * vague statement that something happened.
 *
 * NotificationDispatcher is final, so a real one is wired up with a recording
 * notifier. That has the side benefit of covering the channel routing too.
 */
final class WorkflowNotificationListenerTest extends TestCase
{
    private RecordingNotifier $backend;

    private WorkflowNotificationListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backend = new RecordingNotifier('backend');
        $this->listener = new WorkflowNotificationListener(
            new NotificationDispatcher([$this->backend, new RecordingNotifier('email')]),
        );
    }

    /**
     * @return array{subject: string, message: string, severity: mixed}
     */
    private function only(): array
    {
        self::assertCount(1, $this->backend->sent, 'exactly one notification must have been raised');

        return $this->backend->sent[0];
    }

    #[Test]
    public function aStartedWorkflowIsAnnouncedAsInformation(): void
    {
        $this->listener->onWorkflowStarted(new WorkflowStartedEvent(17, 'Content Pipeline', [], 3));

        $sent = $this->only();
        self::assertSame('Workflow Started', $sent['subject']);
        self::assertStringContainsString('Content Pipeline', $sent['message']);
        self::assertStringContainsString('#17', $sent['message']);
        self::assertSame('info', $sent['severity']);
    }

    #[Test]
    public function aCompletedWorkflowIsAnnouncedAsSuccess(): void
    {
        $this->listener->onWorkflowCompleted(new WorkflowCompletedEvent(17, 'Content Pipeline', new WorkflowContext()));

        $sent = $this->only();
        self::assertSame('Workflow Completed', $sent['subject']);
        self::assertSame('success', $sent['severity']);
    }

    /**
     * The one that must not be quiet. The failing step and the reason both have
     * to be in the text, or the operator has to go digging in the log to learn
     * anything at all.
     */
    #[Test]
    public function aFailedWorkflowNamesTheStepAndTheReason(): void
    {
        $this->listener->onWorkflowFailed(new WorkflowFailedEvent(17, 'generate-intro', 'the model returned nothing'));

        $sent = $this->only();
        self::assertSame('Workflow Failed', $sent['subject']);
        self::assertSame('error', $sent['severity']);
        self::assertStringContainsString('generate-intro', $sent['message']);
        self::assertStringContainsString('the model returned nothing', $sent['message']);
        self::assertStringContainsString('#17', $sent['message']);
    }

    /**
     * A requested review is a workflow that has stopped and is waiting for a
     * person. Warning rather than info, because nothing moves until someone acts.
     */
    #[Test]
    public function aRequestedReviewIsAnnouncedAsWarning(): void
    {
        $this->listener->onReviewRequested(new ReviewRequestedEvent(17, 88, 'Approve the intro'));

        $sent = $this->only();
        self::assertSame('Review Required', $sent['subject']);
        self::assertSame('warning', $sent['severity']);
        self::assertStringContainsString('Approve the intro', $sent['message']);
        self::assertStringContainsString('#88', $sent['message'], 'the task number is what the operator clicks through to');
    }

    /**
     * Every one of these goes to the backend only. Routing a workflow start to
     * the email channel would put internal noise in somebody's inbox.
     */
    #[Test]
    public function nothingIsSentToOtherChannels(): void
    {
        $email = new RecordingNotifier('email');
        $listener = new WorkflowNotificationListener(new NotificationDispatcher([$email]));

        $listener->onWorkflowStarted(new WorkflowStartedEvent(1, 'W', [], 0));
        $listener->onWorkflowCompleted(new WorkflowCompletedEvent(1, 'W', new WorkflowContext()));
        $listener->onWorkflowFailed(new WorkflowFailedEvent(1, 's', 'e'));
        $listener->onReviewRequested(new ReviewRequestedEvent(1, 2, 't'));

        self::assertSame([], $email->sent);
    }
}

final class RecordingNotifier implements NotifierInterface
{
    /** @var list<array{subject: string, message: string, severity: mixed}> */
    public array $sent = [];

    public function __construct(private readonly string $channel) {}

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function supports(string $channel): bool
    {
        return $channel === $this->channel;
    }

    /**
     * @param list<string>         $recipients
     * @param array<string, mixed> $context
     */
    public function notify(string $subject, string $message, array $recipients = [], array $context = []): void
    {
        $this->sent[] = [
            'subject' => $subject,
            'message' => $message,
            'severity' => $context['severity'] ?? null,
        ];
    }
}
