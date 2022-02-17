<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;
use Slub\Infrastructure\VCS\Github\Query\FindReviews;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetPRDetails;
use Slub\Infrastructure\VCS\Github\Query\GetPRInfo;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetPRInfoTest extends TestCase
{
    private GetPRInfo $getPRInfo;

    private GetPRDetails|ObjectProphecy $getPRDetails;

    private GetCIStatus|ObjectProphecy $getCIStatus;

    private FindReviews|ObjectProphecy $findReviews;

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
        $expectedPRIdentifier = 'akeneo/pim-community-dev/1212';
        $expectedRepositoryIdentifier = 'akeneo/pim-community-dev';
        $commitSHA = 'abc2456';
        $expectedCommentsCount = 1;
        $expectedGTMCount = 2;
        $expectedNotGTMCount = 3;
        $checkStatus = CheckStatus::pending();
        $expectedTitle = 'Add new feature';
        $expectedDescription = 'a nice description';
        $expectedAuthorIdentifier = 'sam';
        $expectedAuthorImageUrl = 'https://a_nice_url_image';
        $PRIdentifier = PRIdentifier::fromString($expectedPRIdentifier);
        $expectedAdditions = 10;
        $expectedDeletions = 5;

        $this->getPRDetails
            ->fetch($PRIdentifier)
            ->willReturn([
                'head'  => ['sha' => $commitSHA],
                'state' => 'closed',
                'title' => $expectedTitle,
                'user' => ['login' => $expectedAuthorIdentifier, 'avatar_url' => $expectedAuthorImageUrl],
                'additions' => $expectedAdditions,
                'deletions' => $expectedDeletions,
                'body' => $expectedDescription
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

        self::assertEquals($expectedPRIdentifier, $actualPRInfo->PRIdentifier);
        self::assertEquals($expectedRepositoryIdentifier, $actualPRInfo->repositoryIdentifier);
        self::assertEquals($expectedAuthorIdentifier, $actualPRInfo->authorIdentifier);
        self::assertEquals($expectedAuthorImageUrl, $actualPRInfo->authorImageUrl);
        self::assertEquals($expectedTitle, $actualPRInfo->title);
        self::assertEquals($expectedDescription, $actualPRInfo->description);
        self::assertEquals($expectedGTMCount, $actualPRInfo->GTMCount);
        self::assertEquals($expectedNotGTMCount, $actualPRInfo->notGTMCount);
        self::assertEquals($expectedCommentsCount, $actualPRInfo->comments);
        self::assertEquals($checkStatus, $actualPRInfo->CIStatus);
        self::assertEquals($expectedAdditions, $actualPRInfo->additions);
        self::assertEquals($expectedDeletions, $actualPRInfo->deletions);
        self::assertTrue($actualPRInfo->isMerged);
        self::assertTrue($actualPRInfo->isClosed);
    }
}
