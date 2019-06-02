<?php

declare(strict_types=1);

namespace Tests\Functional;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Client;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class PRMergedTest extends WebTestCase
{
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    /** @var PRRepositoryInterface */
    private $PRRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->GivenAPRToReview();
    }

    /**
     * @test
     */
    public function when_a_pr_is_merged_on_github_it_is_set_to_merged(): void
    {
        $client = $this->WhenAPRIsMerged();

        $this->assertIsMerged(true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // Check Slack calls
    }

    private function assertIsMerged(bool $isMerged)
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PR_IDENTIFIER));
        $this->assertEquals($isMerged, $PR->normalize()['IS_MERGED']);
    }

    private function GivenAPRToReview(): void
    {
        $this->PRRepository->save(
            PR::create(
                PRIdentifier::create(self::PR_IDENTIFIER),
                MessageIdentifier::create('CHANNEL_ID@1111')
            )
        );
    }

    private function WhenAPRIsMerged(): Client
    {
        $client = static::createClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->PRMerged(), $this->get('GITHUB_WEBHOOK_SECRET')));
        $client->request(
            'POST',
            '/vcs/github',
            [],
            [],
            ['HTTP_X-GitHub-Event' => 'pull_request', 'HTTP_X-Hub-Signature' => $signature],
            $this->PRMerged()
        );

        return $client;
    }

    private function PRMerged(): string
    {
        $json = <<<JSON
{
    "pull_request": {
        "number": 10,
        "merged": true
    },
    "repository": {
        "full_name": "SamirBoulil/slub"
    }
}
JSON;

        return $json;
    }
}