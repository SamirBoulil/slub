<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Repository;

use Ramsey\Uuid\Uuid;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlDeliveredEventRepository;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SqlDeliveredEventRepositoryTest extends KernelTestCase
{
    private SqlDeliveredEventRepository $deliveredEventRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->deliveredEventRepository = $this->get('slub.infrastructure.persistence.delivered_event_repository');
    }

    /**
     * @test
     */
    public function it_retrieves_a_delivered_event_by_ID(): void
    {
        $expectedDeliveredEvent = Uuid::uuid4()->toString();
        $this->deliveredEventRepository->save($expectedDeliveredEvent);

        $actualDeliveredEvent = $this->deliveredEventRepository->getBy($expectedDeliveredEvent);

        self::assertEquals($expectedDeliveredEvent, $actualDeliveredEvent);
    }

    /**
     * @test
     */
    public function it_throws_if_the_delivered_event_does_not_exist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->deliveredEventRepository->getBy('UNKNOWN_EVENT');
    }
}
