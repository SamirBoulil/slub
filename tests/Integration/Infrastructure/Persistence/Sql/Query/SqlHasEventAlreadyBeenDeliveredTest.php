<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Query;

use Ramsey\Uuid\Uuid;
use Slub\Infrastructure\Persistence\Sql\Query\SqlHasEventAlreadyBeenDelivered;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlDeliveredEventRepository;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SqlHasEventAlreadyBeenDeliveredTest extends KernelTestCase
{
    /** @var SqlHasEventAlreadyBeenDelivered */
    private $hasEventAlreadyBeenDelivered;

    /** @var SqlDeliveredEventRepository */
    private $deliveredEventRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->hasEventAlreadyBeenDelivered = $this->get('slub.infrastructure.persistence.has_event_already_been_delivered');
        $this->deliveredEventRepository = $this->get('slub.infrastructure.persistence.delivered_event_repository');
        $this->resetDB();
    }

    /**
     * @test
     */
    public function it_tells_if_an_event_has_already_been_delivered()
    {
        $deliveredEvent = Uuid::uuid4()->toString();
        $this->deliveredEventRepository->save($deliveredEvent);

        $result = $this->hasEventAlreadyBeenDelivered->fetch($deliveredEvent);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function it_tells_if_an_event_has_not_already_been_delivered()
    {
        $deliveredEvent = Uuid::uuid4()->toString();

        $result = $this->hasEventAlreadyBeenDelivered->fetch($deliveredEvent);

        self::assertFalse($result);
    }


    private function resetDB(): void
    {
        $sqlPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $sqlPRRepository->reset();
    }
}
