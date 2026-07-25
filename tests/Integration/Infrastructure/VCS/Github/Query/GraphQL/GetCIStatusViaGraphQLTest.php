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
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;
use Slub\Infrastructure\VCS\Github\Query\GraphQL\CIStatusFromPullRequestNode;
use Slub\Infrastructure\VCS\Github\Query\GraphQL\FetchPullRequestNode;
use Slub\Infrastructure\VCS\Github\Query\GraphQL\GetCIStatusViaGraphQL;

/**
 * Behavioral spec of the GraphQL CI status computation. The data provider cases
 * mirror GetCIStatusTest 1:1 so both implementations deduct the same status from
 * the same check landscape.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class GetCIStatusViaGraphQLTest extends TestCase
{
    use ProphecyTrait;

    private const PR_IDENTIFIER = 'SamirBoulil/slub/36';
    private const COMMIT_REF = 'commit_ref';
    private const SUPPORTED_CI_CHECK_1 = 'supported_1';
    private const SUPPORTED_CI_CHECK_2 = 'supported_2';
    private const SUPPORTED_CI_CHECK_3 = 'supported_3';
    private const NOT_SUPPORTED_CI_CHECK_1 = 'unsupported_1';
    private const NOT_SUPPORTED_CI_CHECK_2 = 'unsupported_2';
    private const NOT_SUPPORTED_CI_CHECK_3 = 'unsupported_3';
    private const BUILD_LINK = 'http://my-ci.com/build/123';

    private ObjectProphecy|GithubAPIClientInterface $githubAPIClient;

    private GetCIStatusViaGraphQL $getCIStatus;

    public function setUp(): void
    {
        parent::setUp();
        $this->githubAPIClient = $this->prophesize(GithubAPIClientInterface::class);
        $this->getCIStatus = new GetCIStatusViaGraphQL(
            new FetchPullRequestNode($this->githubAPIClient->reveal(), 'https://api.github.com', new NullLogger()),
            new CIStatusFromPullRequestNode(
                implode(',', [self::SUPPORTED_CI_CHECK_1, self::SUPPORTED_CI_CHECK_2, self::SUPPORTED_CI_CHECK_3])
            )
        );
    }

    /**
     * @test
     * @dataProvider ciStatusesExamples
     */
    public function it_deducts_the_ci_status_from_the_status_check_rollup_contexts(
        array $contextNodes,
        string $expectedCIStatus,
        string $expectedBuildLink
    ): void {
        $this->stubGraphQLCall($this->graphqlResponse($contextNodes));

        $actualCIStatus = $this->getCIStatus->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER), self::COMMIT_REF);

        self::assertEquals($expectedCIStatus, $actualCIStatus->status);
        self::assertEquals($expectedBuildLink, $actualCIStatus->buildLink);
    }

    public function ciStatusesExamples(): array
    {
        return [
            'CI Checks not supported' => [
                [
                    self::checkRun(null, self::NOT_SUPPORTED_CI_CHECK_1),
                    self::checkRun('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_1),
                    self::statusContext('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_1),
                    self::statusContext('PENDING', self::NOT_SUPPORTED_CI_CHECK_2),
                    self::statusContext('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_3),
                ],
                'PENDING',
                '',
            ],
            'All unsupported CI check statuses: green' => [
                [
                    self::checkRun('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_1),
                    self::checkRun('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_2),
                    self::statusContext('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_1),
                    self::statusContext('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_2),
                ],
                'GREEN',
                '',
            ],
            'Supported CI Checks not run' => [
                [
                    self::checkRun(null, self::SUPPORTED_CI_CHECK_1),
                    self::checkRun(null, self::SUPPORTED_CI_CHECK_2),
                    self::checkRun('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_1),
                    self::statusContext('PENDING', self::SUPPORTED_CI_CHECK_1),
                    self::statusContext('PENDING', self::SUPPORTED_CI_CHECK_2),
                    self::statusContext('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_1),
                ],
                'PENDING',
                '',
            ],
            'Multiple CI checks Green' => [
                [
                    self::checkRun('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_1),
                    self::checkRun('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_2),
                    self::statusContext('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_1),
                    self::statusContext('SUCCESS', self::NOT_SUPPORTED_CI_CHECK_2),
                ],
                'GREEN',
                '',
            ],
            'Multiple CI checks Red' => [
                [
                    self::checkRun('FAILURE', self::SUPPORTED_CI_CHECK_1, self::BUILD_LINK),
                    self::checkRun('FAILURE', self::SUPPORTED_CI_CHECK_2, self::BUILD_LINK),
                    self::statusContext('FAILURE', self::SUPPORTED_CI_CHECK_1, self::BUILD_LINK),
                    self::statusContext('FAILURE', self::SUPPORTED_CI_CHECK_2, self::BUILD_LINK),
                ],
                'RED',
                self::BUILD_LINK,
            ],
            'Multiple CI checks Pending' => [
                [
                    self::checkRun(null, self::SUPPORTED_CI_CHECK_1),
                    self::checkRun(null, self::SUPPORTED_CI_CHECK_2),
                    self::statusContext('PENDING', self::SUPPORTED_CI_CHECK_1),
                    self::statusContext('PENDING', self::SUPPORTED_CI_CHECK_2),
                ],
                'PENDING',
                '',
            ],
            'Mixed CI checks statuses: red' => [
                [
                    self::checkRun('FAILURE', self::NOT_SUPPORTED_CI_CHECK_1, self::BUILD_LINK),
                    self::checkRun('SUCCESS', self::SUPPORTED_CI_CHECK_1),
                    self::checkRun(null, self::SUPPORTED_CI_CHECK_2),
                    self::statusContext('FAILURE', self::NOT_SUPPORTED_CI_CHECK_1, self::BUILD_LINK),
                    self::statusContext('SUCCESS', self::SUPPORTED_CI_CHECK_1),
                    self::statusContext('PENDING', self::SUPPORTED_CI_CHECK_2),
                ],
                'RED',
                self::BUILD_LINK,
            ],
            'Mixed CI checks statuses: green' => [
                [
                    self::checkRun('SUCCESS', self::SUPPORTED_CI_CHECK_1),
                    self::checkRun('SUCCESS', self::SUPPORTED_CI_CHECK_2),
                    self::checkRun(null, self::NOT_SUPPORTED_CI_CHECK_1),
                    self::statusContext('SUCCESS', self::SUPPORTED_CI_CHECK_1),
                    self::statusContext('SUCCESS', self::SUPPORTED_CI_CHECK_2),
                    self::statusContext('PENDING', self::NOT_SUPPORTED_CI_CHECK_1),
                ],
                'GREEN',
                '',
            ],
        ];
    }

    /** @test */
    public function it_determines_a_green_ci_if_the_pr_is_mergeable_and_clean(): void
    {
        $this->stubGraphQLCall(
            $this->graphqlResponse(
                [self::checkRun('FAILURE', self::SUPPORTED_CI_CHECK_1, self::BUILD_LINK)],
                'MERGEABLE',
                'CLEAN'
            )
        );

        $actualCIStatus = $this->getCIStatus->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER), self::COMMIT_REF);

        self::assertEquals('GREEN', $actualCIStatus->status);
    }

    /** @test */
    public function it_determines_a_green_ci_when_the_pr_has_no_check_at_all(): void
    {
        $this->stubGraphQLCall($this->graphqlResponse(null));

        $actualCIStatus = $this->getCIStatus->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER), self::COMMIT_REF);

        self::assertEquals('GREEN', $actualCIStatus->status);
    }

    /** @test */
    public function it_does_not_trust_the_merge_state_status_when_it_is_not_clean(): void
    {
        $this->stubGraphQLCall(
            $this->graphqlResponse(
                [
                    self::checkRun('SUCCESS', self::SUPPORTED_CI_CHECK_1),
                    self::statusContext('PENDING', self::NOT_SUPPORTED_CI_CHECK_1),
                ],
                'MERGEABLE',
                'BLOCKED'
            )
        );

        $actualCIStatus = $this->getCIStatus->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER), self::COMMIT_REF);

        self::assertEquals('GREEN', $actualCIStatus->status);
    }

    /** @test */
    public function it_throws_when_the_graphql_response_contains_errors(): void
    {
        $this->stubGraphQLCall(
            new Response(200, [], (string) json_encode([
                'data' => ['repository' => null],
                'errors' => [['type' => 'NOT_FOUND', 'message' => 'Could not resolve to a Repository']],
            ]))
        );

        $this->expectException(\RuntimeException::class);

        $this->getCIStatus->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER), self::COMMIT_REF);
    }

    /** @test */
    public function it_throws_when_the_response_is_not_a_200(): void
    {
        $this->stubGraphQLCall(new Response(502, [], 'Bad gateway'));

        $this->expectException(\RuntimeException::class);

        $this->getCIStatus->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER), self::COMMIT_REF);
    }

    /** @test */
    public function it_throws_when_the_pull_request_is_missing_from_the_response(): void
    {
        $this->stubGraphQLCall(
            new Response(200, [], (string) json_encode(['data' => ['repository' => ['pullRequest' => null]]]))
        );

        $this->expectException(\RuntimeException::class);

        $this->getCIStatus->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER), self::COMMIT_REF);
    }

    private function stubGraphQLCall(Response $response): void
    {
        $this->githubAPIClient->post(
            'https://api.github.com/graphql',
            Argument::that(
                static fn (array $options): bool => str_contains($options['json']['query'], 'statusCheckRollup')
                    && 'SamirBoulil' === $options['json']['variables']['owner']
                    && 'slub' === $options['json']['variables']['name']
                    && 36 === $options['json']['variables']['number']
            ),
            'SamirBoulil/slub'
        )->willReturn($response);
    }

    private function graphqlResponse(
        ?array $contextNodes,
        string $mergeable = 'CONFLICTING',
        string $mergeStateStatus = 'DIRTY'
    ): Response {
        $statusCheckRollup = null === $contextNodes
            ? null
            : ['state' => 'PENDING', 'contexts' => ['nodes' => $contextNodes]];

        return new Response(200, [], (string) json_encode([
            'data' => [
                'repository' => [
                    'pullRequest' => [
                        'mergeable' => $mergeable,
                        'mergeStateStatus' => $mergeStateStatus,
                        'commits' => ['nodes' => [['commit' => ['statusCheckRollup' => $statusCheckRollup]]]],
                    ],
                ],
            ],
        ]));
    }

    private static function checkRun(?string $conclusion, string $name, string $detailsUrl = ''): array
    {
        return ['__typename' => 'CheckRun', 'name' => $name, 'conclusion' => $conclusion, 'detailsUrl' => $detailsUrl];
    }

    private static function statusContext(string $state, string $context, string $targetUrl = ''): array
    {
        return ['__typename' => 'StatusContext', 'context' => $context, 'state' => $state, 'targetUrl' => $targetUrl];
    }
}
