<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use Psr\Log\NullLogger;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\VCSStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;
use Slub\Infrastructure\VCS\Github\Query\FindReviews;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetPRDetails;
use Slub\Infrastructure\VCS\Github\Query\GetVCSStatusFromGithub;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>

 */
class GetVCSStatusFromGithubTest extends WebTestCase
{
    private const PR_COMMIT_REF = 'PR_COMMIT_REF';
    private const EXPECTED_GTMS = 1;
    private const EXPECTED_NOT_GTMS = 2;
    private const EXPECTED_COMMENTS = 3;
    private const EXPECTED_CI_STATUS = 'GREEN';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/36';

    /** @var \Prophecy\Prophecy\ObjectProphecy|GetCIStatus */
    protected $getCIStatusStub;

    /** @var GetVCSStatusFromGithub */
    private $getVCSStatus;

    /** @var \Prophecy\Prophecy\ObjectProphecy|GetPRDetails */
    private $getPRDetailsStub;

    /** @var \Prophecy\Prophecy\ObjectProphecy|FindReviews */
    protected $findReviewsStub;

    public function setUp(): void
    {
        parent::setUp();

        $this->getPRDetailsStub = $this->prophesize(GetPRDetails::class);
        $this->findReviewsStub = $this->prophesize(FindReviews::class);
        $this->getCIStatusStub = $this->prophesize(GetCIStatus::class);
        $this->getVCSStatus = new GetVCSStatusFromGithub(
            $this->getPRDetailsStub->reveal(),
            $this->findReviewsStub->reveal(),
            $this->getCIStatusStub->reveal(),
            new NullLogger()
        );
    }

    /**
     * @test
     * @dataProvider prStatesExamples
     */
    public function it_creates_a_VCS_status_which_is_not_merged(string $prState, bool $expectedIsMerged): void
    {
        $PRIdentifier = PRIdentifier::fromString(self::PR_IDENTIFIER);
        $this->getPRDetailsStub->fetch($PRIdentifier)->willReturn(['head' => ['sha' => self::PR_COMMIT_REF], 'state' => $prState]);
        $this->findReviewsStub->fetch($PRIdentifier)->willReturn([
            FindReviews::GTMS => self::EXPECTED_GTMS,
            FindReviews::NOT_GTMS => self::EXPECTED_NOT_GTMS,
            FindReviews::COMMENTS => self::EXPECTED_COMMENTS,
        ]);
        $this->getCIStatusStub->fetch($PRIdentifier, self::PR_COMMIT_REF)->willReturn(new CheckStatus(self::EXPECTED_CI_STATUS));

        $vcsStatus = $this->getVCSStatus->fetch($PRIdentifier);

        $this->assertVCSStatus($vcsStatus, $expectedIsMerged);
    }

    private function assertVCSStatus(VCSStatus $vcsStatus, bool $expectedIsMerged): void
    {
        $this->assertEquals($vcsStatus->PRIdentifier, self::PR_IDENTIFIER);
        $this->assertEquals(self::EXPECTED_GTMS, $vcsStatus->GTMCount);
        $this->assertEquals(self::EXPECTED_NOT_GTMS, $vcsStatus->notGTMCount);
        $this->assertEquals(self::EXPECTED_COMMENTS, $vcsStatus->comments);
        $this->assertEquals(self::EXPECTED_CI_STATUS, $vcsStatus->checkStatus->status);
        $this->assertEquals($expectedIsMerged, $vcsStatus->isMerged);
    }

    public function prStatesExamples()
    {
        return [
            'PR is closed' => ['closed', true],
            'PR is not closed' => ['open', false],
        ];
    }
}
