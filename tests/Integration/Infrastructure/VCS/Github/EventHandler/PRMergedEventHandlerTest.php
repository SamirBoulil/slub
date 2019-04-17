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
class PRMergedEventHandlerTest extends WebTestCase
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
    public function it_listens_to_merged_PR()
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->PRMerged(), $this->get('GITHUB_WEBHOOK_SECRET')));
        $client->request('POST', '/vcs/github', [], [], ['HTTP_X-GitHub-Event' => 'pull_request', 'HTTP_X-Hub-Signature' => $signature], $this->PRMerged());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertIsMerged(true);
    }

    /**
     * @test
     */
    public function it_does_nothing_if_the_pr_is_not_merged()
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->PRNotMerged(), $this->get('GITHUB_WEBHOOK_SECRET')));
        $client->request('POST', '/vcs/github', [], [], ['HTTP_X-GitHub-Event' => 'pull_request', 'HTTP_X-Hub-Signature' => $signature], $this->PRNotMerged());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertIsMerged(false);
    }

    private function assertIsMerged(bool $isMerged)
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PRIdentifier));
        $this->assertEquals($isMerged, $PR->normalize()['IS_MERGED']);
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

    private function PRMerged(): string
    {
        $json = <<<JSON
{
    "pull_request": {
        "number": 10,
        "merged": false
    },
    "repository": {
        "full_name": "SamirBoulil/slub"
    }
}
JSON;

        return $json;
    }

    private function PRNotMerged(): string
    {
        $json = <<<JSON
{
    "dummy": "value"
}
JSON;

        return $json;
    }
}
