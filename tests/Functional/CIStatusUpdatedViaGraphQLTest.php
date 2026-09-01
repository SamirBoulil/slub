<?php

declare(strict_types=1);

namespace Tests\Functional;

use GuzzleHttp\Psr7\Response;
use Ramsey\Uuid\Uuid;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Tests\GithubApiClientMock;
use Tests\WebTestCase;

/**
 * Same scenario as CIStatusUpdatedTest, with FF_GITHUB_GRAPHQL on: the CI status
 * of the PR is computed with a single GraphQL call instead of three REST calls.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class CIStatusUpdatedViaGraphQLTest extends WebTestCase
{
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';
    private const GRAPHQL_URL = '127.0.0.1:8081/graphql';

    private PRRepositoryInterface $PRRepository;
    private GithubApiClientMock $githubAPIClientMock;

    private KernelBrowser $client;

    public function setUp(): void
    {
        self::activateGraphQLFeatureFlag('1');
        parent::setUp();
        $this->client = self::getClient();
        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->githubAPIClientMock = $this->get('slub.infrastructure.vcs.github.client.github_api_client');
    }

    public function tearDown(): void
    {
        self::activateGraphQLFeatureFlag('0');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_computes_the_ci_status_with_a_single_graphql_call(): void
    {
        $this->Given_a_PR_is_to_review();
        $this->When_the_supported_check_run_is_green_in_the_status_check_rollup();
        $this->Then_the_PR_should_be_green();
        $this->And_the_github_api_was_only_called_once();
    }

    private function Given_a_PR_is_to_review(): void
    {
        $this->PRRepository->save(
            PR::create(
                PRIdentifier::create(self::PR_IDENTIFIER),
                ChannelIdentifier::fromString('squad-raccoons'),
                WorkspaceIdentifier::fromString('akeneo'),
                MessageIdentifier::create('CHANNEL_ID@1111'),
                AuthorIdentifier::fromString('sam'),
                Title::fromString('Add new feature')
            )
        );
    }

    private function When_the_supported_check_run_is_green_in_the_status_check_rollup(): void
    {
        $this->githubAPIClientMock->stubUrlWith(
            self::GRAPHQL_URL,
            new Response(
                200,
                [],
                (string) json_encode(
                    [
                        'data' => [
                            'repository' => [
                                'pullRequest' => [
                                    'mergeable' => 'MERGEABLE',
                                    'mergeStateStatus' => 'BLOCKED',
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
                                                                    'conclusion' => 'SUCCESS',
                                                                    'detailsUrl' => '',
                                                                ],
                                                                [
                                                                    '__typename' => 'StatusContext',
                                                                    'context' => 'unsupported_check',
                                                                    'state' => 'PENDING',
                                                                    'targetUrl' => '',
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                )
            )
        );

        $this->callAPI($this->supportedGreenCI());
        self::assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    private function Then_the_PR_should_be_green(): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PR_IDENTIFIER));
        $this->assertEquals('GREEN', $PR->normalize()['CI_STATUS']['BUILD_RESULT']);
    }

    private function And_the_github_api_was_only_called_once(): void
    {
        self::assertEquals([self::GRAPHQL_URL], $this->githubAPIClientMock->calledUrls());
    }

    private function callAPI(string $data): void
    {
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $data, $this->get('GITHUB_WEBHOOK_SECRET')));
        $this->client->request(
            'POST',
            '/vcs/github',
            [],
            [],
            [
                'HTTP_X-GitHub-Event' => 'status',
                'HTTP_X-Hub-Signature' => $signature,
                'HTTP_X-Github-Delivery' => Uuid::uuid4()->toString(),
            ],
            $data
        );
    }

    private function supportedGreenCI(): string
    {
        return <<<JSON
{
  "sha": "commit-ref",
  "context": "travis - phpunit",
  "name": "travis",
  "state": "success",
  "number": 10,
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}
JSON;
    }

    private static function activateGraphQLFeatureFlag(string $value): void
    {
        $_ENV['FF_GITHUB_GRAPHQL'] = $value;
        $_SERVER['FF_GITHUB_GRAPHQL'] = $value;
        putenv(sprintf('FF_GITHUB_GRAPHQL=%s', $value));
    }
}
