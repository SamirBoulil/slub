<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\EventHandler;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Client;
use Tests\Integration\Infrastructure\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class StatusUpdatedEventHandlerTest extends WebTestCase
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
    public function it_listens_to_green_ci_for_supported_statuses()
    {
        $client = $this->handleEvent($this->supportedGreenCI());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertGreen();
    }

    /**
     * @test
     */
    public function it_does_not_listen_to_unsupported_and_green_check_runs()
    {
        $client = $this->handleEvent($this->unsupportedGreenCI());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertPending();
    }

    /**
     * @test
     */
    public function it_listens_to_all_red_ci()
    {
        $client = $this->handleEvent($this->redCIUnsupportedCheck());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRed();
    }

    /**
     * @test
     */
    public function it_listens_to_ci_pending()
    {
        $client = $this->handleEvent($this->pendingCheckRun());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertPending();
    }

    /**
     * @test
     */
    public function it_throws_for_unsupported_conclusion()
    {
        $this->expectException(\Exception::class);
        $client = $this->handleEvent($this->unsupportedResult());

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

    private function supportedGreenCI(): string
    {
        $json = <<<JSON
{
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

    private function unsupportedGreenCI(): string
    {
        $json = <<<JSON
{
  "name": "UNSUPPORTED STATUS",
  "state": "success",
  "number": 10,
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}
JSON;

        return $json;
    }

    private function redCIUnsupportedCheck(): string
    {
        $json = <<<JSON
{
  "name": "UNSUPPORTED STATUS",
  "state": "failure",
  "number": 10,
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}
JSON;

        return $json;
    }

    private function pendingCheckRun(): string
    {
        $json = <<<JSON
{
  "name": "UNSUPPORTED STATUS",
  "state": "pending",
  "number": 10,
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
  "name": "travis",
  "state": "UNSUPPORTED_RESULT",
  "number": 10,
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

    private function handleEvent(string $data): Client
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $data, $this->get('GITHUB_WEBHOOK_SECRET')));
        $client->request(
            'POST',
            '/vcs/github',
            [],
            [],
            ['HTTP_X-GitHub-Event' => 'status', 'HTTP_X-Hub-Signature' => $signature],
            $data
        );
        return $client;
    }
}
