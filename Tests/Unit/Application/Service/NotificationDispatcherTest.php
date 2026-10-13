<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Application\Service;

use Koh\AiWorkflows\Application\Service\NotificationDispatcher;
use Koh\AiWorkflows\Domain\Contract\NotifierInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NotificationDispatcherTest extends TestCase
{
    #[Test]
    public function dispatchesToMatchingNotifier(): void
    {
        $email = $this->createMock(NotifierInterface::class);
        $email->method('supports')->willReturnCallback(fn(string $ch) => 'email' === $ch);
        $email->expects(self::once())->method('notify')->with('Subj', 'Msg', ['a@b.com'], []);

        $slack = $this->createMock(NotifierInterface::class);
        $slack->method('supports')->willReturnCallback(fn(string $ch) => 'slack' === $ch);
        $slack->expects(self::never())->method('notify');

        $d = new NotificationDispatcher([$email, $slack]);
        $d->dispatch('email', 'Subj', 'Msg', ['a@b.com']);
    }

    #[Test]
    public function dispatchToAllNotifiesEveryone(): void
    {
        $n1 = $this->createMock(NotifierInterface::class);
        $n1->expects(self::once())->method('notify');
        $n2 = $this->createMock(NotifierInterface::class);
        $n2->expects(self::once())->method('notify');

        $d = new NotificationDispatcher([$n1, $n2]);
        $d->dispatchToAll('Subj', 'Msg');
    }

    #[Test]
    public function worksWithEmptyNotifierList(): void
    {
        $d = new NotificationDispatcher([]);
        $d->dispatch('email', 'Subj', 'Msg');
        self::assertTrue(true);
    }
}
