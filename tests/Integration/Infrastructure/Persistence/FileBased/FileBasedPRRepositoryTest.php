<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\FileBased;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Infrastructure\Persistence\FileBased\FileBasedPRRepository;

class FileBasedPRRepositoryTest extends PersistenceTestCase
{
    /** @var FileBasedPRRepository */
    private $fileBasedPRRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->fileBasedPRRepository = new FileBasedPRRepository($this->filePath);
    }

    /**
     * @test
     */
    public function it_saves_a_pr_and_returns_it()
    {
        $identifier = PRIdentifier::create('akeneo', 'pim-community-dev', '1111');
        $savedPR = PR::create($identifier);

        $this->fileBasedPRRepository->save($savedPR);
        $fetchedPR = $this->fileBasedPRRepository->getBy($identifier);

        $this->assertSame($fetchedPR->normalize(), $savedPR->normalize());
    }

    /**
     * @test
     */
    public function it_throws_if_it_does_not_find_the_pr()
    {
        $this->expectException(PRNotFoundException::class);
        $this->fileBasedPRRepository->getBy(PRIdentifier::fromString('unknown/unknown/unknown'));
    }
}
