<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Query;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;
use Slub\Infrastructure\VCS\Github\Query\FindReviews;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetPRDetails;
use Slub\Infrastructure\VCS\Github\Query\GetPRInfo;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetPRInfoTest extends TestCase
{
    /** @var GetPRInfo */
    private $getPRInfo;

    /** @var \Prophecy\Prophecy\ObjectProphecy|GetPRDetails */
    private $getPRDetails;

    /** @var \Prophecy\Prophecy\ObjectProphecy|GetCIStatus */
    private $getCIStatus;

    /** @var \Prophecy\Prophecy\ObjectProphecy|FindReviews */
    private $findReviews;

    public function setUp(): void
    {
        parent::setUp();
        $this->getPRDetails = $this->prophesize(GetPRDetails::class);
        $this->findReviews = $this->prophesize(FindReviews::class);
        $this->getCIStatus = $this->prophesize(GetCIStatus::class);
        $this->getPRInfo = new GetPRInfo($this->getPRDetails->reveal(), $this->findReviews->reveal(), $this->getCIStatus->reveal());
    }

    /**
     * @test
     */
    public function it_creates_a_PR_info(): void
    {
        $PRIdentifier = PRIdentifier::fromString('akeneo/pim-community-dev');
        $commitSHA = 'abc2456';
        $expectedCommentsCount = 1;
        $expectedGTMCount = 2;
        $expectedNotGTMCount = 3;
        $checkStatus = new CheckStatus('PENDING');
        $this->getPRDetails
            ->fetch($PRIdentifier)
            ->willReturn([
                'head'  => ['sha' => $commitSHA],
                'state' => 'closed',
            ]);
        $this->findReviews
            ->fetch($PRIdentifier)
            ->willReturn([
                FindReviews::COMMENTS => $expectedCommentsCount,
                FindReviews::GTMS     => $expectedGTMCount,
                FindReviews::NOT_GTMS => $expectedNotGTMCount,
            ]);
        $this->getCIStatus
            ->fetch($PRIdentifier, $commitSHA)
            ->willReturn($checkStatus);

        $actualPRInfo = $this->getPRInfo->fetch($PRIdentifier);

        self::assertEquals($PRIdentifier->stringValue(), $actualPRInfo->PRIdentifier);
        self::assertEquals($expectedGTMCount, $actualPRInfo->GTMCount);
        self::assertEquals($expectedNotGTMCount, $actualPRInfo->notGTMCount);
        self::assertEquals($expectedCommentsCount, $actualPRInfo->comments);
        self::assertEquals($checkStatus, $actualPRInfo->CIStatus);
        self::assertTrue($actualPRInfo->isMerged);
    }
}
