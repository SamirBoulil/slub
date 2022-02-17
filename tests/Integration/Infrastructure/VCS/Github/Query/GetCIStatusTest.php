<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckRunStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetMergeableState;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetStatusChecksStatus;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetCIStatusTest extends WebTestCase
{
    private const PR_IDENTIFIER = 'SamirBoulil/slub/36';
    private const COMMIT_REF = 'commit_ref';
    private const BUILD_LINK = 'http://my-ci.com/build/123';

    private ObjectProphecy $getMergeableState;

    private ObjectProphecy $getStatusCheckStatus;

    private ObjectProphecy $getCheckRunStatus;

    private GetCIStatus $getCIStatus;

    public function setUp(): void
    {
        parent::setUp();
        $this->getMergeableState = $this->prophesize(GetMergeableState::class);
        $this->getCheckRunStatus = $this->prophesize(GetCheckRunStatus::class);
        $this->getStatusCheckStatus = $this->prophesize(GetStatusChecksStatus::class);

        $this->getCIStatus = new GetCIStatus(
            $this->getMergeableState->reveal(),
            $this->getCheckRunStatus->reveal(),
            $this->getStatusCheckStatus->reveal(),
            new NullLogger()
        );
    }

    /**
     * @test
     * @dataProvider ciStatusesExamples
     */
    public function it_uses_the_ci_statuses_when_the_check_suite_is_not_failed(
        string $checkRunStatus,
        string $statusCheckStatus,
        string $expectedCIStatus,
        string $expectedBuildLink
    ): void {
        self::assertTrue(false);
        $this->mockIndependentResults($checkRunStatus, $statusCheckStatus, $expectedBuildLink);

        $actualCIStatus = $this->getCIStatus();

        self::assertEquals($expectedCIStatus, $actualCIStatus->status);
        self::assertEquals($expectedBuildLink, $actualCIStatus->buildLink);
    }

    public function test_it_determines_a_green_ci_if_the_pr_is_mergeable(
    ): void {
        $prIdentifierArgument = Argument::that(
            static fn (PRIdentifier $PRIdentifier) => $PRIdentifier->equals($PRIdentifier::fromString(self::PR_IDENTIFIER))
        );
        $this->getMergeableState->fetch($prIdentifierArgument)->willReturn(true);

        $actualCIStatus = $this->getCIStatus();

        self::assertEquals('GREEN', $actualCIStatus->status);
    }

    public function ciStatusesExamples(): array
    {
        return [
            'check run result (GREEN)'                                                         => [
                'GREEN',
                'PENDING',
                'GREEN',
                ''
            ],
            'check run result (RED)'                                                           => [
                'RED',
                'PENDING',
                'RED',
                self::BUILD_LINK
            ],
            'check run is "PENDING", the CI result depends on the status check result (GREEN)' => [
                'PENDING',
                'GREEN',
                'GREEN',
                ''
            ],
            'check run is "PENDING", the CI result depends on the status check result (RED)'   => [
                'PENDING',
                'RED',
                'RED',
                ''
            ],
            'check run is RED then the status is RED'                                          => [
                'RED',
                'GREEN',
                'RED',
                self::BUILD_LINK
            ],
            'status check is RED then the status is RED'                                       => [
                'GREEN',
                'RED',
                'RED',
                self::BUILD_LINK
            ],
            'if both status check and check runs are GREEN then the status is GREEN'           => [
                'GREEN',
                'GREEN',
                'GREEN',
                ''
            ],
        ];
    }

    private function mockIndependentResults(
        string $checkRunStatus,
        string $statusCheckStatus,
        string $buildLink
    ): void {
        $prIdentifierArgument = Argument::that(
            static fn (PRIdentifier $PRIdentifier) => $PRIdentifier->equals($PRIdentifier::fromString(self::PR_IDENTIFIER))
        );
        $commitRefArgument = Argument::that(
            static fn (string $commitRef) => self::COMMIT_REF === $commitRef
        );

        $this->getMergeableState->fetch($prIdentifierArgument)->willReturn(false);
        $this->getCheckRunStatus->fetch($prIdentifierArgument, $commitRefArgument)->willReturn(
            new CheckStatus($checkRunStatus, $buildLink)
        );
        $this->getStatusCheckStatus->fetch($prIdentifierArgument, $commitRefArgument)->willReturn(
            new CheckStatus($statusCheckStatus, $buildLink)
        );
    }

    private function getCIStatus(): CheckStatus
    {
        return $this->getCIStatus->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER), self::COMMIT_REF);
    }
}
