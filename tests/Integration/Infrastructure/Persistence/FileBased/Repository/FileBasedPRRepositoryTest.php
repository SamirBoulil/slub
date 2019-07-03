<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\FileBased\Repository;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Infrastructure\Persistence\FileBased\Repository\FileBasedPRRepository;
use Slub\Infrastructure\Persistence\FileBased\Repository\SqlPRRepository;
use Tests\Integration\Infrastructure\KernelTestCase;

class FileBasedPRRepositoryTest extends KernelTestCase
{
    /** @var FileBasedPRRepository */
    private $fileBasedPRRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->fileBasedPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
    }

    /**
     * @test
     */
    public function it_saves_a_pr_and_returns_it()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');
        $savedPR = PR::create(
            $identifier, ChannelIdentifier::fromString('squad-raccoons'), MessageIdentifier::fromString('1')
        );

        $this->fileBasedPRRepository->save($savedPR);
        $fetchedPR = $this->fileBasedPRRepository->getBy($identifier);

        $this->assertSame($fetchedPR->normalize(), $savedPR->normalize());
    }

    /**
     * @test
     * @throws PRNotFoundException
     */
    public function it_throws_if_it_does_not_find_the_pr()
    {
        $this->expectException(PRNotFoundException::class);
        $this->fileBasedPRRepository->getBy(PRIdentifier::fromString('unknown/unknown/unknown'));
    }

    /**
     * @test
     * @throws PRNotFoundException
     */
    public function it_resets_itself()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');
        $this->fileBasedPRRepository->save(PR::create(
            $identifier, ChannelIdentifier::fromString('squad-raccoons'), MessageIdentifier::fromString('1')
        ));
        $this->fileBasedPRRepository->reset();

        $this->expectException(PRNotFoundException::class);
        $this->fileBasedPRRepository->getBy($identifier);
    }
}
