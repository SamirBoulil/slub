<?php

declare(strict_types=1);

namespace Tests\Functional;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use Ramsey\Uuid\Uuid;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Client;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CIStatusUpdatedTest extends WebTestCase
{
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var MockWebServer */
    private $githubServer;

    public function setUp(): void
    {
        parent::setUp();

        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->githubServer = new MockWebServer((int) $this->get('GITHUB_PORT'));
        $this->githubServer->start();
    }

    public function tearDown()
    {
        $this->githubServer->stop();
    }

    /**
     * @test
     */
    public function it_listens_to_green_ci_for_supported_statuses()
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
        $this->githubServer->setResponseOfPath('/repos/', new Response('yolo'));
    }

    private function Then_the_PR_should_be_green()
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PR_IDENTIFIER));
        $this->assertEquals('GREEN', $PR->normalize()['CI_STATUS']['BUILD_RESULT']);
    }

    private function callAPI(string $data): Client
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $data, $this->get('GITHUB_WEBHOOK_SECRET')));
        $client->request(
            'POST',
            '/vcs/github',
            [],
            [],
            ['HTTP_X-GitHub-Event' => 'status', 'HTTP_X-Hub-Signature' => $signature, 'HTTP_X-Github-Delivery' => Uuid::uuid4()->toString()],
            $data
        );

        return $client;
    }

    private function When_a_PR_status_is_green(): void
    {
        $this->githubServer->setResponseOfPath(
            '/repos/SamirBoulil/slub/commits/commit-ref/check-runs',
            new ResponseStack(
                new Response(
                    (string) json_encode([
                        'check_runs' => [
                            ['name' => 'travis', 'conclusion' => 'success', 'status' => 'completed'],
                        ],
                    ])
                )
            )
        );
        $this->githubServer->setResponseOfPath(
            '/repos/SamirBoulil/slub/statuses/commit-ref',
            new ResponseStack(
                new Response(
                    (string) json_encode([
                        ['context' => 'travis', 'state' => 'success'],
                    ])
                )
            )
        );

        $client = $this->callAPI($this->supportedGreenCI());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    private function supportedGreenCI(): string
    {
        $json = <<<JSON
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

        return $json;
    }
}
