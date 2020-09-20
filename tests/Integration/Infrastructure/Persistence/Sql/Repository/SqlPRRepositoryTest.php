<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Repository;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\BuildLink;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Reviewer\ReviewerName;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
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
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $savedPR->red(BuildLink::fromURL('https://my-ci.com/build/123'));

        $this->sqlPRRepository->save($savedPR);
        $fetchedPR = $this->sqlPRRepository->getBy($identifier);

        self::assertSame($savedPR->normalize(), $fetchedPR->normalize());
    }

    /**
     * @test
     */
    public function it_updates_a_pr()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');
        $savedPR = PR::create(
            $identifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $reviewerName = ReviewerName::fromString('samir');
        $this->sqlPRRepository->save($savedPR);

        $updatedPR = $savedPR;
        $updatedPR->notGTM($reviewerName);
        $updatedPR->GTM($reviewerName);
        $updatedPR->comment($reviewerName);
        $updatedPR->green();
        $updatedPR->close(true);
        $updatedPR->putToReviewAgainViaMessage(
            ChannelIdentifier::fromString('brazil-team'),
            MessageIdentifier::fromString('5151')
        );
        $this->sqlPRRepository->save($updatedPR);

        $fetchedPR = $this->sqlPRRepository->getBy($identifier);
        $this->assertSame($updatedPR->normalize(), $fetchedPR->normalize());
    }

    /**
     * @test
     */
    public function it_puts_a_pr_to_review_that_has_been_closed()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');
        $savedPR = PR::create(
            $identifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $savedPR->close(true);
        $this->sqlPRRepository->save($savedPR);

        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');
        $updatedPR = PR::create(
            $identifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $this->sqlPRRepository->save($updatedPR);

        $fetchedPR = $this->sqlPRRepository->getBy($identifier);
        self::assertSame($updatedPR->normalize(), $fetchedPR->normalize());
    }

    /**
     * @test
     */
    public function it_returns_all_PR_ordered_by_is_merged()
    {
        $this->sqlPRRepository->save(
            PR::fromNormalized(
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'AUTHOR_IDENTIFIER' => 'sam',
                    'TITLE' => 'Add new feature',
                    'GTMS' => 1,
                    'NOT_GTMS' => 1,
                    'COMMENTS' => 1,
                    'CI_STATUS' => ['BUILD_RESULT' => 'PENDING', 'BUILD_LINK' => ''],
                    'IS_MERGED' => false,
                    'MESSAGE_IDS' => ['1', '2'],
                    'CHANNEL_IDS'       => ['squad-raccoons'],
                    'WORKSPACE_IDS' => ['akeneo'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'CLOSED_AT' => null,
                ]
            )
        );
        $this->sqlPRRepository->save(
            PR::fromNormalized(
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/2222',
                    'AUTHOR_IDENTIFIER' => 'sam',
                    'TITLE' => 'Add new feature',
                    'GTMS' => 1,
                    'NOT_GTMS' => 1,
                    'COMMENTS' => 1,
                    'CI_STATUS' => ['BUILD_RESULT' => 'PENDING', 'BUILD_LINK' => ''],
                    'IS_MERGED' => true,
                    'MESSAGE_IDS' => ['1', '2'],
                    'CHANNEL_IDS'       => ['squad-raccoons'],
                    'WORKSPACE_IDS' => ['akeneo'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'CLOSED_AT' => null,
                ]
            )
        );
        $this->sqlPRRepository->save(
            PR::fromNormalized(
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/3333',
                    'AUTHOR_IDENTIFIER' => 'sam',
                    'TITLE' => 'Add new feature',
                    'GTMS' => 1,
                    'NOT_GTMS' => 1,
                    'COMMENTS' => 1,
                    'CI_STATUS' => ['BUILD_RESULT' => 'PENDING', 'BUILD_LINK' => ''],
                    'IS_MERGED' => false,
                    'MESSAGE_IDS' => ['1', '2'],
                    'CHANNEL_IDS'       => ['squad-raccoons'],
                    'WORKSPACE_IDS' => ['akeneo'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'CLOSED_AT' => null,
                ]
            )
        );
        $actualPRs = $this->sqlPRRepository->all();
        $this->assertPRs(
            [
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'AUTHOR_IDENTIFIER' => 'sam',
                    'TITLE' => 'Add new feature',
                    'GTMS' => 1,
                    'NOT_GTMS' => 1,
                    'COMMENTS' => 1,
                    'CI_STATUS' => ['BUILD_RESULT' => 'PENDING', 'BUILD_LINK' => ''],
                    'IS_MERGED' => false,
                    'CHANNEL_IDS'       => ['squad-raccoons'],
                    'WORKSPACE_IDS' => ['akeneo'],
                    'MESSAGE_IDS' => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'CLOSED_AT' => null,
                ],
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/3333',
                    'AUTHOR_IDENTIFIER' => 'sam',
                    'TITLE' => 'Add new feature',
                    'GTMS' => 1,
                    'NOT_GTMS' => 1,
                    'COMMENTS' => 1,
                    'CI_STATUS' => ['BUILD_RESULT' => 'PENDING', 'BUILD_LINK' => ''],
                    'IS_MERGED' => false,
                    'CHANNEL_IDS'       => ['squad-raccoons'],
                    'WORKSPACE_IDS' => ['akeneo'],
                    'MESSAGE_IDS' => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'CLOSED_AT' => null,
                ],
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/2222',
                    'AUTHOR_IDENTIFIER' => 'sam',
                    'TITLE' => 'Add new feature',
                    'GTMS' => 1,
                    'NOT_GTMS' => 1,
                    'COMMENTS' => 1,
                    'CI_STATUS' => ['BUILD_RESULT' => 'PENDING', 'BUILD_LINK' => ''],
                    'IS_MERGED' => true,
                    'CHANNEL_IDS'       => ['squad-raccoons'],
                    'WORKSPACE_IDS' => ['akeneo'],
                    'MESSAGE_IDS' => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => '1560175073',
                    'CLOSED_AT' => null,
                ],
            ],
            $actualPRs
        );
    }

    /**
     * @test
     *
     * @throws PRNotFoundException
     */
    public function it_throws_if_it_does_not_find_the_pr()
    {
        $this->expectException(PRNotFoundException::class);
        $this->sqlPRRepository->getBy(PRIdentifier::fromString('unknown/unknown/unknown'));
    }

    /**
     * @test
     *
     * @throws PRNotFoundException
     */
    public function it_resets_itself()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');
        $this->sqlPRRepository->save(
            PR::create(
                $identifier,
                ChannelIdentifier::fromString('squad-raccoons'),
                WorkspaceIdentifier::fromString('akeneo'),
                MessageIdentifier::fromString('1'),
                AuthorIdentifier::fromString('sam'),
                Title::fromString('Add new feature')
            )
        );
        $this->sqlPRRepository->reset();

        $this->expectException(PRNotFoundException::class);
        $this->sqlPRRepository->getBy($identifier);
    }

    /**
     * @test
     *
     * @throws PRNotFoundException
     */
    public function it_finds_every__open_prs_not_gtmed_twice()
    {
        $PRInReviewNotGTMedIdentifier = 'akeneo/pim-community-dev/1';
        $this->createPRInReview($PRInReviewNotGTMedIdentifier, 0, false);
        $this->createPRInReview('akeneo/pim-community-dev/2', 2, false);
        $this->createPRInReview('akeneo/pim-community-dev/3', 0, true);
        $this->createClosedPRNotMerged('akeneo/pim-community-dev/4');

        $PRs = $this->sqlPRRepository->findPRToReviewNotGTMed();

        self::assertCount(1, $PRs);
        /** @var PR $PR */
        $PR = current($PRs);
        self::assertEquals($PRInReviewNotGTMedIdentifier, $PR->normalize()['IDENTIFIER']);
    }

    /**
     * @test
     */
    public function it_deletes_a_PR_that_has_been_published()
    {
        $PRIdentifier = PRIdentifier::fromString('akeneo/pim-community-dev/1');
        $this->createPRInReview($PRIdentifier->stringValue(), 0, false);

        $this->sqlPRRepository->unpublishPR($PRIdentifier);

        $this->assertEmpty($this->sqlPRRepository->all());
    }

    /**
     * @param array $expectedPRs
     * @param PR[]  $actualPRs
     */
    private function assertPRs(array $expectedPRs, array $actualPRs): void
    {
        $normalizedFetchedPR = [];
        foreach ($actualPRs as $actualPR) {
            $normalizedFetchedPR[] = $actualPR->normalize();
        }

        self::assertSame($expectedPRs, $normalizedFetchedPR);
    }

    private function createPRInReview(string $PRIdentifier, $GTMs, $isMerged): void
    {
        $identifier = PRIdentifier::create($PRIdentifier);
        $PR = PR::create(
            $identifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $reviewerName = ReviewerName::fromString('samir');
        for ($i = 0; $i < $GTMs; ++$i) {
            $PR->GTM($reviewerName);
        }
        if ($isMerged) {
            $PR->close(true);
        }
        $this->sqlPRRepository->save($PR);
    }

    private function createClosedPRNotMerged(string $PRIdentifier): void
    {
        $identifier = PRIdentifier::create($PRIdentifier);
        $PR = PR::create(
            $identifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $PR->close(false);
        $this->sqlPRRepository->save($PR);
    }
}
