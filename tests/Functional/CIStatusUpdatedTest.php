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
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CIStatusUpdatedTest extends WebTestCase
{
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    private PRRepositoryInterface $PRRepository;
    private GithubApiClientMock $githubAPIClientMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = self::getClient();
        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->githubAPIClientMock = $this->get('slub.infrastructure.vcs.github.client.github_api_client');
    }

    /**
     * @test
     */
    public function it_listens_to_green_ci_for_supported_statuses(): void
    {
        $this->Given_a_PR_is_to_review();
        $this->When_a_PR_status_is_green();
        $this->Then_the_PR_should_be_green();
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
        $this->githubAPIClientMock->stubUrlWith('/repos/', new Response(200, [], 'yolo'));
    }

    private function Then_the_PR_should_be_green(): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PR_IDENTIFIER));
        $this->assertEquals('GREEN', $PR->normalize()['CI_STATUS']['BUILD_RESULT']);
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

    private function When_a_PR_status_is_green(): void
    {
        $this->githubAPIClientMock->stubUrlWith(
            '/app/installations/installation_id/access_tokens',
            new Response(
                201,
                [],
                (string)json_encode(['token' => 'v1.1f699f1069f60xxx'], JSON_THROW_ON_ERROR),
            )
        );
        $this->githubAPIClientMock->stubUrlWith(
            'https://api.github.com/repos/SamirBoulil/slub/pulls/10',
            new Response(
                200,
                [],
                (string)json_encode(['mergeable' => true, 'mergeable_state' => 'clean'])
            )
        );
        $this->githubAPIClientMock->stubUrlWith(
            '/repos/SamirBoulil/slub/commits/commit-ref/check-runs',
            new Response(
                200,
                [],
                (string)json_encode(
                    [
                        'check_runs' => [
                            [
                                'name' => 'travis',
                                'conclusion' => 'success',
                                'status' => 'completed',
                            ],
                        ],
                    ]
                )
            )
        );
        $this->githubAPIClientMock->stubUrlWith(
            '/repos/SamirBoulil/slub/statuses/commit-ref',
            new Response(
                200,
                [],
                (string)json_encode(
                    [
                        ['context' => 'travis', 'state' => 'success'],
                    ]
                )
            )
        );

        $this->callAPI($this->supportedGreenCI());
        self::assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    private function supportedGreenCI(): string
    {
        return <<<JSON
{
  "sha": "commit-ref",
  "name": "travis",
  "state": "success",
  "number": 10,
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}
JSON;
    }
}
