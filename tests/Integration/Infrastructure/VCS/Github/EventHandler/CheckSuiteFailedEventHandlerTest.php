<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\EventHandler;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tests\Integration\Infrastructure\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class CheckSuiteFailedEventHandlerTest extends WebTestCase
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
    public function it_listens_to_failing_check_suite()
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->failedCheckSuite(), $this->get('GITHUB_WEBHOOK_SECRET')));

        $client->request('POST', '/vcs/github', [], [], ['HTTP_X-GitHub-Event' => 'check_suite', 'HTTP_X-Hub-Signature' => $signature], $this->failedCheckSuite());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRed();
    }

    /**
     * @test
     */
    public function it_does_not_do_anything_for_unsupported_results()
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->unsupportedResult(), $this->get('GITHUB_WEBHOOK_SECRET')));

        $client->request('POST', '/vcs/github', [], [], ['HTTP_X-GitHub-Event' => 'check_suite', 'HTTP_X-Hub-Signature' => $signature], $this->unsupportedResult());

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

    private function failedCheckSuite(): string
    {
        $json = <<<JSON
{
  "action": "completed",
  "check_suite": {
    "status": "completed",
    "conclusion": "failure",
    "pull_requests": [
      {
        "url": "https://api.github.com/repos/SamirBoulil/slub/pulls/10",
        "number": 10
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
  "check_suite": {
    "conclusion": "WRONG_CONCLUSION"
  }
}
JSON;

        return $json;
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
