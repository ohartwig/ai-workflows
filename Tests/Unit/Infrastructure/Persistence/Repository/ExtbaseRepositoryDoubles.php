<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Persistence\Repository;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Doubles for the Extbase query chain, recording what a repository asked for.
 *
 * The value is not that a query ran — it is *which* constraints went in and
 * whether the storage-page restriction was lifted. These repositories serve
 * workflow records that do not live on the plugin's storage page, so leaving the
 * restriction on makes every lookup silently return nothing.
 */
trait ExtbaseRepositoryDoubles
{
    /** @var list<array{string, string, mixed}> */
    private array $constraints = [];

    private bool $storagePageRespected = true;

    private int $andGroupCount = 0;

    private string $queriedType = '';

    private function resetQueryRecording(): void
    {
        $this->constraints = [];
        $this->storagePageRespected = true;
        $this->andGroupCount = 0;
        $this->queriedType = '';
    }

    /**
     * @param list<object> $rows
     */
    private function queryReturning(array $rows): QueryInterface&MockObject
    {
        $settings = $this->createMock(QuerySettingsInterface::class);
        $settings->method('setRespectStoragePage')->willReturnCallback(
            function (bool $respect) use ($settings): QuerySettingsInterface {
                $this->storagePageRespected = $respect;

                return $settings;
            },
        );

        $result = $this->createMock(QueryResultInterface::class);
        $result->method('getFirst')->willReturn($rows[0] ?? null);
        $result->method('toArray')->willReturn($rows);

        $query = $this->createMock(QueryInterface::class);
        $query->method('getQuerySettings')->willReturn($settings);
        $query->method('execute')->willReturn($result);
        $query->method('matching')->willReturnSelf();

        foreach (['equals', 'lessThan', 'greaterThan'] as $operator) {
            $query->method($operator)->willReturnCallback(
                function (string $property, mixed $operand) use ($operator): ComparisonInterface {
                    $this->constraints[] = [$operator, $property, $operand];

                    // logicalAnd() declares ConstraintInterface parameters, so a
                    // plain token would be rejected before it is ever recorded.
                    // What went in is tracked above rather than in the value.
                    return $this->createMock(ComparisonInterface::class);
                },
            );
        }

        $query->method('logicalAnd')->willReturnCallback(
            function (mixed ...$parts): AndInterface {
                ++$this->andGroupCount;

                return $this->createMock(AndInterface::class);
            },
        );

        return $query;
    }

    private function persistenceManagerFor(QueryInterface $query): PersistenceManagerInterface&MockObject
    {
        $manager = $this->createMock(PersistenceManagerInterface::class);
        $manager->method('createQueryForType')->willReturnCallback(
            function (string $type) use ($query): QueryInterface {
                $this->queriedType = $type;

                return $query;
            },
        );

        return $manager;
    }

    /**
     * @return list<array{string, string, mixed}>
     */
    private function recordedConstraints(): array
    {
        return $this->constraints;
    }
}
