<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Query;

use Ramsey\Uuid\Uuid;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Persistence\Sql\Query\SqlHasEventAlreadyBeenDelivered;
use Slub\Infrastructure\Persistence\Sql\Query\SqlIsPRInReview;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlDeliveredEventRepository;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SqlIsPRInReviewTest extends KernelTestCase
{
    private const PR_IDENTIFIER = 'samirboulil/slub/123';
    private SqlIsPRInReview $IsPRInReview;

    private Connection $sqlConnection;
    public function setUp(): void
    {
        parent::setUp();
        $this->IsPRInReview = $this->get('slub.infrastructure.persistence.is_pr_in_review');
        $this->resetDB();
    }

    /**
     * @test
     */
    public function it_tells_if_a_pr_is_already_in_review_or_not(): void
    {
        $prIdentifierInReview = PRIdentifier::fromString(self::PR_IDENTIFIER);
        $prIdentifierNotInReview = PRIdentifier::fromString('not_in_review');

        $this->createPR($prIdentifierInReview);

        self::assertTrue($this->IsPRInReview->fetch($prIdentifierInReview));
        self::assertFalse($this->IsPRInReview->fetch($prIdentifierNotInReview));
    }

    private function resetDB(): void
    {
        $sqlPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $sqlPRRepository->reset();
    }

    private function createPR(PRIdentifier $prIdentifier): void
    {
        /** @var PRRepositoryInterface $PRRepository */
        $PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $PR = PR::create(
            $prIdentifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $PRRepository->save($PR);
    }
}
