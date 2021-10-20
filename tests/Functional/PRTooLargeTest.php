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

    /** @var PRRepositoryInterface */
    private $PRRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->GivenALargePRToReview();
    }

    /**
     * @test
     */
    public function when_a_large_pr_is_opened_on_github_it_is_set_to_large(): void
    {
        $client = $this->WhenALargePRIsOpened();

        $this->assertIsLarge(true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    private function WhenALargePRIsOpened(): KernelBrowser
    {
        $client = self::getClient();
        $signature = sprintf('sha1=%s', hash_hmac('sha1', $this->LargePR(), $this->get('GITHUB_WEBHOOK_SECRET')));
        

        return $client;
    }

    private function assertIsLarge(bool $isLarge): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString(self::PR_IDENTIFIER));
        $this->assertEquals($isLarge, $PR->normalize()['IS_LARGE']);
    }

    private function GivenALargePRToReview(): void
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

    private function LargePR(): string
    {
        return <<<JSON
{
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
