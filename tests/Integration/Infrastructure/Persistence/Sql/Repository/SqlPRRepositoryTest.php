<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Repository;

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
        $savedPR = PR::create(
            $identifier,
            MessageIdentifier::fromString('1')
        );

        $this->sqlPRRepository->save($savedPR);
        $fetchedPR = $this->sqlPRRepository->getBy($identifier);

        $this->assertSame($savedPR->normalize(), $fetchedPR->normalize());
    }

    /**
     * @test
     */
    public function it_updates_a_pr()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');
        $savedPR = PR::create(
            $identifier,
            MessageIdentifier::fromString('1')
        );
        $this->sqlPRRepository->save($savedPR);

        $updatedPR = $savedPR;
        $updatedPR->notGTM();
        $updatedPR->GTM();
        $updatedPR->comment();
        $updatedPR->green();
        $updatedPR->merged();
        $updatedPR->putToReviewAgainViaMessage(MessageIdentifier::fromString('5151'));
        $this->sqlPRRepository->save($updatedPR);

        $fetchedPR = $this->sqlPRRepository->getBy($identifier);
        $this->assertSame($updatedPR->normalize(), $fetchedPR->normalize());
    }

    /**
     * @test
     */
    public function it_returns_all_PR_ordered_by_is_merged()
    {
        $this->sqlPRRepository->save(
            PR::fromNormalized(
                [
                    'IDENTIFIER'       => 'akeneo/pim-community-dev/1111',
                    'GTMS'             => 1,
                    'NOT_GTMS'         => 1,
                    'COMMENTS'         => 1,
                    'CI_STATUS'        => 'PENDING',
                    'IS_MERGED'        => false,
                    'MESSAGE_IDS'      => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'MERGED_AT'        => null
                ]
            )
        );
        $this->sqlPRRepository->save(
            PR::fromNormalized(
                [
                    'IDENTIFIER'  => 'akeneo/pim-community-dev/2222',
                    'GTMS'        => 1,
                    'NOT_GTMS'    => 1,
                    'COMMENTS'    => 1,
                    'CI_STATUS'   => 'PENDING',
                    'IS_MERGED'   => true,
                    'MESSAGE_IDS' => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'MERGED_AT'        => null
                ]
            )
        );
        $this->sqlPRRepository->save(
            PR::fromNormalized(
                [
                    'IDENTIFIER'  => 'akeneo/pim-community-dev/3333',
                    'GTMS'        => 1,
                    'NOT_GTMS'    => 1,
                    'COMMENTS'    => 1,
                    'CI_STATUS'   => 'PENDING',
                    'IS_MERGED'   => false,
                    'MESSAGE_IDS' => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'MERGED_AT'        => null
                ]
            )
        );
        $actualPRs = $this->sqlPRRepository->all();
        $this->assertPRs(
            [
                [
                    'IDENTIFIER'  => 'akeneo/pim-community-dev/1111',
                    'GTMS'        => 1,
                    'NOT_GTMS'    => 1,
                    'COMMENTS'    => 1,
                    'CI_STATUS'   => 'PENDING',
                    'IS_MERGED'   => false,
                    'MESSAGE_IDS' => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'MERGED_AT'        => null
                ],
                [
                    'IDENTIFIER'  => 'akeneo/pim-community-dev/3333',
                    'GTMS'        => 1,
                    'NOT_GTMS'    => 1,
                    'COMMENTS'    => 1,
                    'CI_STATUS'   => 'PENDING',
                    'IS_MERGED'   => false,
                    'MESSAGE_IDS' => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'MERGED_AT'        => null
                ],
                [
                    'IDENTIFIER'  => 'akeneo/pim-community-dev/2222',
                    'GTMS'        => 1,
                    'NOT_GTMS'    => 1,
                    'COMMENTS'    => 1,
                    'CI_STATUS'   => 'PENDING',
                    'IS_MERGED'   => true,
                    'MESSAGE_IDS' => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'MERGED_AT'        => null
                ],
            ],
            $actualPRs
        );
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
        $this->sqlPRRepository->save(
            PR::create(
                $identifier,
                MessageIdentifier::fromString('1')
            )
        );
        $this->sqlPRRepository->reset();

        $this->expectException(PRNotFoundException::class);
        $this->sqlPRRepository->getBy($identifier);
    }

    /**
     * @param array $expectedPRs
     * @param PR[] $actualPRs
     */
    private function assertPRs(array $expectedPRs, array $actualPRs): void
    {
        $normalizedFetchedPR = [];
        foreach ($actualPRs as $actualPR) {
            $normalizedFetchedPR[] = $actualPR->normalize();
        }

        $this->assertSame($expectedPRs, $normalizedFetchedPR);
    }
}
