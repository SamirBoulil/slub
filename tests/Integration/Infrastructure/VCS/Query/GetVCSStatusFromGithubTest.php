<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\VCSStatus;
use Slub\Infrastructure\VCS\Github\Query\GetVCSStatusFromGithub;
use Tests\Integration\Infrastructure\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class GetVCSStatusFromGithubTest extends WebTestCase
{
    /** @var GetVCSStatusFromGithub */
    private $getVCSStatus;

    public function setUp(): void
    {
        parent::setUp();

        $this->getVCSStatus = $this->get('slub.infrastructure.vcs.github.query.get_vcs_status_from_github');
    }

    /**
     * @test
     */
    public function it_successfully_gets_the_vcs_status_for_the_pr(): void
    {
        $PRIdentifier = PRIdentifier::fromString('SamirBoulil/slub/36');
        $vcsStatus = $this->getVCSStatus->fetch($PRIdentifier);
        $this->assertVCSStatus($vcsStatus, $PRIdentifier);
    }

    private function assertVCSStatus(VCSStatus $vcsStatus, PRIdentifier $PRIdentifier): void
    {
        $this->assertEquals($vcsStatus->PRIdentifier, $PRIdentifier->stringValue());
        $this->assertEquals(0, $vcsStatus->GTMCount);
        $this->assertEquals(0, $vcsStatus->notGTMCount);
        $this->assertEquals(0, $vcsStatus->comments);
        $this->assertEquals('GREEN', $vcsStatus->CIStatus);
        $this->assertEquals(true, $vcsStatus->isMerged);
    }
}
