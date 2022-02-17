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
    private const SUPPORTED_CI_CHECK_1 = 'supported_1';
    private const SUPPORTED_CI_CHECK_2 = 'supported_2';
    private const SUPPORTED_CI_CHECK_3 = 'supported_3';
    private const NOT_SUPPORTED_CI_CHECK_1 = 'unsupported_1';
    private const NOT_SUPPORTED_CI_CHECK_2 = 'unsupported_2';
    private const NOT_SUPPORTED_CI_CHECK_3 = 'unsupported_3';
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
            new NullLogger(),
            implode(',', [self::SUPPORTED_CI_CHECK_1, self::SUPPORTED_CI_CHECK_2, self::SUPPORTED_CI_CHECK_3]),
        );
    }

    /**
     * @test
     * @dataProvider ciStatusesExamples
     */
    public function it_uses_the_ci_statuses_when_the_check_suite_is_not_failed(
        array $checkRunStatuses,
        array $statusCheckStatuses,
        string $expectedCIStatus,
        string $expectedBuildLink
    ): void {
        $prIdentifierArgument = Argument::that(
            static fn(PRIdentifier $PRIdentifier) => $PRIdentifier->equals(
                $PRIdentifier::fromString(self::PR_IDENTIFIER)
            )
        );
        $commitRefArgument = Argument::that(
            static fn(string $commitRef) => self::COMMIT_REF === $commitRef
        );

        $this->getMergeableState->fetch($prIdentifierArgument)->willReturn(false);
        $this->getCheckRunStatus->fetch($prIdentifierArgument, $commitRefArgument)
            ->willReturn($checkRunStatuses);
        $this->getStatusCheckStatus->fetch($prIdentifierArgument, $commitRefArgument)
            ->willReturn($statusCheckStatuses);

        $actualCIStatus = $this->getCIStatus->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER), self::COMMIT_REF);

        self::assertEquals($expectedCIStatus, $actualCIStatus->status);
        self::assertEquals($expectedBuildLink, $actualCIStatus->buildLink);
    }
    public function ciStatusesExamples(): array
    {
        return [
            'CI Checks not supported' => [
                [
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_1),
                    CheckStatus::pending(self::NOT_SUPPORTED_CI_CHECK_1),
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_1),
                ],
                [
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_1),
                    CheckStatus::pending(self::NOT_SUPPORTED_CI_CHECK_2),
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_3),
                ],
                'PENDING',
                '',
            ],
            'All unsupported CI check statuses: green' => [
                [
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_1),
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_2),
                ],
                [
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_1),
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_2),
                ],
                'GREEN',
                '',
            ],
            'Supported CI Checks not run' => [
                [
                    CheckStatus::pending(self::SUPPORTED_CI_CHECK_1),
                    CheckStatus::pending(self::SUPPORTED_CI_CHECK_2),
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_1),
                ],
                [
                    CheckStatus::pending(self::SUPPORTED_CI_CHECK_1),
                    CheckStatus::pending(self::SUPPORTED_CI_CHECK_2),
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_1),

                ],
                'PENDING',
                '',
            ],
            'Multiple CI checks Green' => [
                [
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_1),
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_2),
                ],
                [
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_1),
                    CheckStatus::green(self::NOT_SUPPORTED_CI_CHECK_2),
                ],
                'GREEN',
                '',
            ],
            'Multiple CI checks Red' => [
                [
                    CheckStatus::red(self::SUPPORTED_CI_CHECK_1, self::BUILD_LINK),
                    CheckStatus::red(self::SUPPORTED_CI_CHECK_2, self::BUILD_LINK),
                ],
                [
                    CheckStatus::red(self::SUPPORTED_CI_CHECK_1, self::BUILD_LINK),
                    CheckStatus::red(self::SUPPORTED_CI_CHECK_2, self::BUILD_LINK),
                ],
                'RED',
                self::BUILD_LINK,
            ],
            'Multiple CI checks Pending' => [
                [
                    CheckStatus::pending(self::SUPPORTED_CI_CHECK_1),
                    CheckStatus::pending(self::SUPPORTED_CI_CHECK_2),
                ],
                [
                    CheckStatus::pending(self::SUPPORTED_CI_CHECK_1),
                    CheckStatus::pending(self::SUPPORTED_CI_CHECK_2),
                ],
                'PENDING',
                '',
            ],
            'Mixed CI checks statuses: red' => [
                [
                    CheckStatus::red(self::NOT_SUPPORTED_CI_CHECK_1, self::BUILD_LINK),
                    CheckStatus::green(self::SUPPORTED_CI_CHECK_1),
                    CheckStatus::pending(self::SUPPORTED_CI_CHECK_2),
                ],
                [
                    CheckStatus::red(self::NOT_SUPPORTED_CI_CHECK_1, self::BUILD_LINK),
                    CheckStatus::green(self::SUPPORTED_CI_CHECK_1),
                    CheckStatus::pending(self::SUPPORTED_CI_CHECK_2),
                ],
                'RED',
                self::BUILD_LINK,
            ],
            'Mixed CI checks statuses: green' => [
                [
                    CheckStatus::green(self::SUPPORTED_CI_CHECK_1),
                    CheckStatus::green(self::SUPPORTED_CI_CHECK_2),
                    CheckStatus::pending(self::NOT_SUPPORTED_CI_CHECK_1),
                ],
                [
                    CheckStatus::green(self::SUPPORTED_CI_CHECK_1),
                    CheckStatus::green(self::SUPPORTED_CI_CHECK_2),
                    CheckStatus::pending(self::NOT_SUPPORTED_CI_CHECK_1),
                ],
                'GREEN',
                '',
            ],
        ];
    }

    public function test_it_determines_a_green_ci_if_the_pr_is_mergeable(): void
    {
        $prIdentifierArgument = Argument::that(
            static fn(PRIdentifier $PRIdentifier) => $PRIdentifier->equals(
                $PRIdentifier::fromString(self::PR_IDENTIFIER)
            )
        );
        $this->getMergeableState->fetch($prIdentifierArgument)->willReturn(true);

        $actualCIStatus = $this->getCIStatus->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER), self::COMMIT_REF);

        self::assertEquals('GREEN', $actualCIStatus->status);
    }
}
