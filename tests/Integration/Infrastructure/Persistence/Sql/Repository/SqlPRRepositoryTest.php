<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Query;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlPRRepository;
use Tests\Integration\Infrastructure\KernelTestCase;

class SqlPRRepositoryTest extends KernelTestCase
{
    /** @var SqlPRRepository */
    private $sqlPRRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->sqlPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->sqlPRRepository->reset();
    }

    /**
     * @test
     */
    public function it_saves_a_pr_and_returns_it()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');
        $savedPR = PR::create($identifier, MessageIdentifier::fromString('1'));

        $this->sqlPRRepository->save($savedPR);
        $fetchedPR = $this->sqlPRRepository->getBy($identifier);

        $this->assertSame($fetchedPR->normalize(), $savedPR->normalize());
    }

    /**
     * @test
     * @throws PRNotFoundException
     */
    public function it_throws_if_it_does_not_find_the_pr()
    {
        $this->expectException(PRNotFoundException::class);
        $this->sqlPRRepository->getBy(PRIdentifier::fromString('unknown/unknown/unknown'));
    }

    /**
     * @test
     * @throws PRNotFoundException
     */
    public function it_resets_itself()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');
        $this->sqlPRRepository->save(PR::create($identifier, MessageIdentifier::fromString('1')));
        $this->sqlPRRepository->reset();

        $this->expectException(PRNotFoundException::class);
        $this->sqlPRRepository->getBy($identifier);
    }
}
