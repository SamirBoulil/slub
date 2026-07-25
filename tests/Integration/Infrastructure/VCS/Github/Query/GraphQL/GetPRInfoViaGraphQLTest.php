<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query\GraphQL;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlPRCommitsRepository;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;
use Slub\Infrastructure\VCS\Github\Query\GraphQL\CIStatusFromPullRequestNode;
use Slub\Infrastructure\VCS\Github\Query\GraphQL\FetchPullRequestNode;
use Slub\Infrastructure\VCS\Github\Query\GraphQL\GetPRInfoViaGraphQL;

/**
 * Mirrors GetPRInfoTest: the GraphQL implementation must build the same PRInfo
 * from a single API call as the legacy implementation does from five.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class GetPRInfoViaGraphQLTest extends TestCase
{
    use ProphecyTrait;

    private const PR_IDENTIFIER = 'akeneo/pim-community-dev/1212';
    private const REPOSITORY_IDENTIFIER = 'akeneo/pim-community-dev';
    private const COMMIT_SHA = 'abc2456';

    private ObjectProphecy|GithubAPIClientInterface $githubAPIClient;

    private ObjectProphecy|SqlPRCommitsRepository $prCommitsRepository;

    private GetPRInfoViaGraphQL $getPRInfo;

    public function setUp(): void
    {
        parent::setUp();
        $this->githubAPIClient = $this->prophesize(GithubAPIClientInterface::class);
        $this->prCommitsRepository = $this->prophesize(SqlPRCommitsRepository::class);
        $this->getPRInfo = new GetPRInfoViaGraphQL(
            new FetchPullRequestNode($this->githubAPIClient->reveal(), 'https://api.github.com', new NullLogger()),
            new CIStatusFromPullRequestNode('travis,jenkins'),
            $this->prCommitsRepository->reveal(),
            new NullLogger()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_PR_info_from_a_single_graphql_call(): void
    {
        $this->stubGraphQLCall($this->graphqlResponse($this->pullRequestNode()));
        $this->prCommitsRepository
            ->saveHeadCommit(self::REPOSITORY_IDENTIFIER, self::COMMIT_SHA, '1212')
            ->shouldBeCalled();

        $actualPRInfo = $this->getPRInfo->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER));

        self::assertEquals(self::PR_IDENTIFIER, $actualPRInfo->PRIdentifier);
        self::assertEquals(self::REPOSITORY_IDENTIFIER, $actualPRInfo->repositoryIdentifier);
        self::assertEquals('sam', $actualPRInfo->authorIdentifier);
        self::assertEquals('https://a_nice_url_image', $actualPRInfo->authorImageUrl);
        self::assertEquals('Add new feature', $actualPRInfo->title);
        self::assertEquals('a nice description', $actualPRInfo->description);
        self::assertEquals(2, $actualPRInfo->GTMCount);
        // Parity with the legacy FindReviews: it counts a "REFUSED" review state that
        // Github never sends, so the not-GTM count stays 0 even with a CHANGES_REQUESTED review.
        self::assertEquals(0, $actualPRInfo->notGTMCount);
        self::assertEquals(1, $actualPRInfo->comments);
        self::assertEquals('PENDING', $actualPRInfo->CIStatus->status);
        self::assertEquals(10, $actualPRInfo->additions);
        self::assertEquals(5, $actualPRInfo->deletions);
        self::assertTrue($actualPRInfo->isMerged);
        self::assertTrue($actualPRInfo->isClosed);
    }

    /**
     * @test
     */
    public function it_creates_a_PR_info_for_an_open_PR_with_no_description(): void
    {
        $pullRequestNode = $this->pullRequestNode();
        $pullRequestNode['state'] = 'OPEN';
        $pullRequestNode['body'] = null;
        $this->stubGraphQLCall($this->graphqlResponse($pullRequestNode));
        $this->prCommitsRepository->saveHeadCommit(Argument::cetera())->shouldBeCalled();

        $actualPRInfo = $this->getPRInfo->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER));

        self::assertFalse($actualPRInfo->isMerged);
        self::assertFalse($actualPRInfo->isClosed);
        self::assertEquals('', $actualPRInfo->description);
    }

    /**
     * @test
     */
    public function it_still_returns_the_PR_info_when_recording_the_head_commit_fails(): void
    {
        $this->stubGraphQLCall($this->graphqlResponse($this->pullRequestNode()));
        $this->prCommitsRepository
            ->saveHeadCommit(Argument::cetera())
            ->willThrow(new \RuntimeException('The database is down'));

        $actualPRInfo = $this->getPRInfo->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER));

        self::assertEquals('Add new feature', $actualPRInfo->title);
    }

    /**
     * @test
     */
    public function it_throws_when_the_graphql_response_contains_errors(): void
    {
        $this->stubGraphQLCall(
            new Response(200, [], (string) json_encode([
                'data' => ['repository' => null],
                'errors' => [['type' => 'NOT_FOUND', 'message' => 'Could not resolve to a Repository']],
            ]))
        );

        $this->expectException(\RuntimeException::class);

        $this->getPRInfo->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER));
    }

    private function pullRequestNode(): array
    {
        return [
            'title' => 'Add new feature',
            'body' => 'a nice description',
            'state' => 'CLOSED',
            'author' => ['login' => 'sam', 'avatarUrl' => 'https://a_nice_url_image'],
            'additions' => 10,
            'deletions' => 5,
            'headRefOid' => self::COMMIT_SHA,
            'mergeable' => 'MERGEABLE',
            'mergeStateStatus' => 'BLOCKED',
            'reviews' => [
                'nodes' => [
                    ['state' => 'APPROVED'],
                    ['state' => 'APPROVED'],
                    ['state' => 'COMMENTED'],
                    ['state' => 'CHANGES_REQUESTED'],
                ],
            ],
            'commits' => [
                'nodes' => [
                    [
                        'commit' => [
                            'statusCheckRollup' => [
                                'state' => 'PENDING',
                                'contexts' => [
                                    'nodes' => [
                                        [
                                            '__typename' => 'CheckRun',
                                            'name' => 'travis',
                                            'conclusion' => null,
                                            'detailsUrl' => '',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function graphqlResponse(array $pullRequestNode): Response
    {
        return new Response(
            200,
            [],
            (string) json_encode(['data' => ['repository' => ['pullRequest' => $pullRequestNode]]])
        );
    }

    private function stubGraphQLCall(Response $response): void
    {
        $this->githubAPIClient->post(
            'https://api.github.com/graphql',
            Argument::that(
                static fn (array $options): bool => str_contains($options['json']['query'], 'statusCheckRollup')
                    && str_contains($options['json']['query'], 'reviews')
                    && 'akeneo' === $options['json']['variables']['owner']
                    && 'pim-community-dev' === $options['json']['variables']['name']
                    && 1212 === $options['json']['variables']['number']
            ),
            self::REPOSITORY_IDENTIFIER
        )->willReturn($response);
    }
}
