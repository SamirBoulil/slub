<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\EventHandler;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Integration\Infrastructure\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class CheckRunSuccessEventHandlerTest extends WebTestCase
{
    private const PRIdentifier = 'SamirBoulil/slub/10';

    /** @var PRRepositoryInterface */
    private $PRRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->createDefaultPR();
    }

    /**
     * @test
     */
    public function it_listens_to_green_ci()
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->greenCI(), $this->get('GITHUB_WEBHOOK_SECRET')));

        $client->request('POST', '/vcs/github', [], [], ['HTTP_X-GitHub-Event' => 'check_run', 'HTTP_X-Hub-Signature' => $signature], $this->greenCI());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertGreen();
    }

    /**
     * @test
     */
    public function it_does_not_do_anything_for_unsupported_results()
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->unsupportedResult(), $this->get('GITHUB_WEBHOOK_SECRET')));

        $client->request('POST', '/vcs/github', [], [], ['HTTP_X-GitHub-Event' => 'check_run', 'HTTP_X-Hub-Signature' => $signature], $this->unsupportedResult());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertPending();
    }

    private function createDefaultPR(): void
    {
        $this->PRRepository->save(
            PR::create(
                PRIdentifier::create(self::PRIdentifier),
                MessageIdentifier::create('CHANNEL_ID@1111')
            )
        );
    }

    private function greenCI(): string
    {
        $json = <<<JSON
{
  "action": "completed",
  "check_run": {
    "status": "completed",
    "conclusion": "success",
    "name": "travis",
    "check_suite": {
      "pull_requests": [
        {
          "number": 10
        }
      ]
    },
    "pull_requests": [
      {
        "url": "https://api.github.com/repos/SamirBoulil/slub/pulls/26",
        "number": 26
      }
    ]
  },
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}
JSON;

        return $json;
    }

    private function unsupportedResult(): string
    {
        $json = <<<JSON
{
  "action": "completed",
  "check_run": {
    "status": "completed",
    "conclusion": "WRONG_CONCLUSION",
    "name": "travis",
    "check_suite": {
      "pull_requests": [
        {
          "number": 10
        }
      ]
    },
    "pull_requests": [
      {
        "url": "https://api.github.com/repos/SamirBoulil/slub/pulls/26",
        "number": 26
      }
    ]
  },
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}
JSON;

        return $json;
    }

    private function assertGreen()
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PRIdentifier));
        $this->assertEquals('GREEN', $PR->normalize()['CI_STATUS']);
    }

    private function assertPending()
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PRIdentifier));
        $this->assertEquals('PENDING', $PR->normalize()['CI_STATUS']);
    }
}
