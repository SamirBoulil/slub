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
class CheckRunEventHandlerTest extends WebTestCase
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
    public function it_listens_to_green_ci_for_supported_check_runs()
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->supportedGreenCI(), $this->get('GITHUB_WEBHOOK_SECRET')));

        $client->request('POST', '/vcs/github', [], [], ['HTTP_X-GitHub-Event' => 'check_run', 'HTTP_X-Hub-Signature' => $signature], $this->supportedGreenCI());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertGreen();
    }

    /**
     * @test
     */
    public function it_does_not_to_listen_to_green_ci_for_unsupported_check_runs()
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->unsupportedGreenCI(), $this->get('GITHUB_WEBHOOK_SECRET')));

        $client->request('POST', '/vcs/github', [], [], ['HTTP_X-GitHub-Event' => 'check_run', 'HTTP_X-Hub-Signature' => $signature], $this->unsupportedGreenCI());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertPending();
    }

    /**
     * @test
     */
    public function it_listens_to_red_ci()
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->redCI(), $this->get('GITHUB_WEBHOOK_SECRET')));

        $client->request('POST', '/vcs/github', [], [], ['HTTP_X-GitHub-Event' => 'check_run', 'HTTP_X-Hub-Signature' => $signature], $this->redCI());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRed();
    }

    /**
     * @test
     */
    public function it_does_nothing_for_unsupported_results()
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->unsupportedResult(), $this->get('GITHUB_WEBHOOK_SECRET')));

        $client->request('POST', '/vcs/github', [], [], ['HTTP_X-GitHub-Event' => 'check_run', 'HTTP_X-Hub-Signature' => $signature], $this->unsupportedResult());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertPending();
    }

    /**
     * @test
     */
    public function it_does_nothing_for_unsupported_completion()
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->unsupportedAction(), $this->get('GITHUB_WEBHOOK_SECRET')));

        $client->request('POST', '/vcs/github', [], [], ['HTTP_X-GitHub-Event' => 'check_run', 'HTTP_X-Hub-Signature' => $signature], $this->unsupportedAction());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertPending();
    }

    private function createDefaultPR(): void
    {
        $this->PRRepository->save(
            PR::create(
                PRIdentifier::create(self::PRIdentifier), MessageIdentifier::create('CHANNEL_ID@1111'), 0, 0, 0, 'pending', false
            )
        );
    }

    private function supportedGreenCI(): string
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
    }
  },
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}
JSON;

        return $json;
    }

    private function unsupportedGreenCI(): string
    {
        $json = <<<JSON
{
  "action": "completed",
  "check_run": {
    "status": "completed",
    "conclusion": "success",
    "name": "UNSUPPORTED_CHECK_RUN",
    "check_suite": {
      "pull_requests": [
        {
          "number": 10
        }
      ]
    }
  },
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}
JSON;

        return $json;
    }

    private function redCI(): string
    {
        $json = <<<JSON
{
  "action": "completed",
  "check_run": {
    "status": "completed",
    "conclusion": "failure",
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

    private function unsupportedAction(): string
    {
        $json = <<<JSON
{
  "action": "WRONG_COMPLETION",
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

    private function assertGreen()
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PRIdentifier));
        $this->assertEquals('GREEN', $PR->normalize()['CI_STATUS']);
    }

    private function assertRed()
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PRIdentifier));
        $this->assertEquals('RED', $PR->normalize()['CI_STATUS']);
    }

    private function assertPending()
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PRIdentifier));
        $this->assertEquals('PENDING', $PR->normalize()['CI_STATUS']);
    }
}
