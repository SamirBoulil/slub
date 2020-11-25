<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Query\FindReviews;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class FindReviewsTest extends WebTestCase
{
    private const REPOSITORY_NAME = 'SamirBoulil/slub';
    private const PR_NUMBER = '36';

    /** @var FindReviews */
    private $findReviews;

    /** @var ObjectProphecy|GithubAPIClient */
    private $githubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->githubAPIClient = $this->prophesize(GithubAPIClient::class);
        $this->findReviews = new FindReviews($this->githubAPIClient->reveal());
    }

    /**
     * @test
     * @dataProvider reviewsExamples
     */
    public function it_successfully_fetches_the_reviews(array $someReviews, array $expectedCounts): void
    {
        $this->githubAPIClient->get(
            sprintf('https://api.github.com/repos/%s/pulls/%s/reviews', self::REPOSITORY_NAME, self::PR_NUMBER),
            [],
            self::REPOSITORY_NAME
        )->willReturn(new Response(200, [], (string) json_encode($someReviews)));

        $actualReviews = $this->findReviews->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'));

        self::assertEquals($expectedCounts, $actualReviews);
    }

    public function reviewsExamples(): array
    {
        return [
            'No reviews' => [[], ['GTMS' => 0, 'NOT_GTMS' => 0, 'COMMENTS' => 0]],
            'Some reviews' => [
                [['state' => 'APPROVED'], ['state' => 'REFUSED'], ['state' => 'COMMENTED']],
                ['GTMS' => 1, 'NOT_GTMS' => 1, 'COMMENTS' => 1],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_malformed(): void
    {
        $this->githubAPIClient->get(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(200, [], (string) '{'));
        $this->expectException(\RuntimeException::class);

        $this->findReviews->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'));
    }
}
