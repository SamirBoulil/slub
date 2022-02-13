<?php

declare(strict_types=1);

namespace Tests\Functional;

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
use Tests\WebTestCase;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class PRTooLargeTest extends WebTestCase
{
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    private PRRepositoryInterface $PRRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->GivenAPRToReviewThatIsNotLarge();
    }

    /**
     * @test
     */
    public function when_a_large_pr_is_opened_on_github_it_is_set_to_large(): void
    {
        $client = $this->WhenALargePRIsOpened();

        $this->assertPRIsLarge();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    private function WhenALargePRIsOpened(): KernelBrowser
    {
        $client = self::getClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->largePREvent(), $this->get('GITHUB_WEBHOOK_SECRET')));
        $client->request(
            'POST',
            '/vcs/github',
            [],
            [],
            ['HTTP_X-GitHub-Event' => 'pull_request', 'HTTP_X-Hub-Signature' => $signature, 'HTTP_X-Github-Delivery' => Uuid::uuid4()->toString()],
            $this->largePREvent()
        );

        return $client;
    }

    private function assertPRIsLarge(): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PR_IDENTIFIER));
        $this->assertTrue($PR->normalize()['IS_TOO_LARGE']);
    }

    private function GivenAPRToReviewThatIsNotLarge(): void
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

    private function largePREvent(): string
    {
        return <<<JSON
{
    "action": "synchronize",
    "pull_request": {
        "number": 10,
        "additions": 1271,
        "deletions": 43
    },
    "repository": {
        "full_name": "SamirBoulil/slub"
    }
}
JSON;
    }
}
