<?php

declare(strict_types=1);

namespace Tests\Functional;

use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Tests\GithubApiClientMock;
use Tests\WebTestCase;

/**
 * A `status` webhook for a PR Slub does not track must be acknowledged with a 200
 * without spending a single GitHub API call (Phase 1: skip untracked PRs).
 *
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CIStatusUpdatedForUntrackedPRTest extends WebTestCase
{
    private GithubApiClientMock $githubAPIClientMock;

    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = self::getClient();
        $this->githubAPIClientMock = $this->get('slub.infrastructure.vcs.github.client.github_api_client');
    }

    /**
     * @test
     */
    public function it_does_not_call_the_github_api_when_the_pr_is_not_in_review(): void
    {
        $this->When_a_status_is_received_for_an_untracked_pr();

        self::assertEquals(200, $this->client->getResponse()->getStatusCode());
        self::assertSame([], $this->githubAPIClientMock->calledUrls());
    }

    private function When_a_status_is_received_for_an_untracked_pr(): void
    {
        $this->callAPI($this->supportedGreenCI());
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
  "name": "SamirBoulil/slub",
  "state": "success",
  "number": 10,
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}
JSON;
    }
}
