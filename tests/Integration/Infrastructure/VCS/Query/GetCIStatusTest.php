<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Query;

use Monolog\Logger;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckRunStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetStatusChecksStatus;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class GetCIStatusTest extends WebTestCase
{
    private const PR_IDENTIFIER = 'SamirBoulil/slub/36';
    private const COMMIT_REF = 'commit_ref';

    /** @var ObjectProphecy */
    private $getStatusCheckStatus;

    /** @var ObjectProphecy */
    private $getCheckRunStatus;

    /** @var ObjectProphecy */
    private $getCheckSuiteStatus;

    /** @var GetCIStatus */
    private $getCIStatus;

    public function setUp(): void
    {
        parent::setUp();
        $this->getCheckRunStatus = $this->prophesize(GetCheckRunStatus::class);
        $this->getStatusCheckStatus = $this->prophesize(GetStatusChecksStatus::class);

        $this->getCIStatus = new GetCIStatus(
            $this->getCheckRunStatus->reveal(),
            $this->getStatusCheckStatus->reveal(),
            new Logger('dummy')
        );
    }

    /**
     * @test
     * @dataProvider ciStatusesExamples
     */
    public function it_uses_the_ci_statuses_when_the_check_suite_is_not_failed(
        ?string $checkRunStatus,
        ?string $statusCheckStatus,
        string $expectedCIStatus
    ): void {
        $this->mockIndependentResults($checkRunStatus, $statusCheckStatus);

        $actualCIStatus = $this->getCIStatus();

        self::assertEquals($expectedCIStatus, $actualCIStatus);
    }

    public function ciStatusesExamples(): array
    {
        return [
            'check run result (GREEN)'   => [
                'GREEN',
                'PENDING',
                'GREEN',
            ],
            'check run result (RED)'     => [
                'RED',
                'PENDING',
                'RED',
            ],
            'check run is "PENDING", the CI result depends on the status check result (GREEN)'   => [
                'PENDING',
                'GREEN',
                'GREEN',
            ],
            'check run is "PENDING", the CI result depends on the status check result (RED)'     => [
                'PENDING',
                'RED',
                'RED',
            ],
            'check run is RED then the status is RED'                             => [
                'RED',
                'GREEN',
                'RED',
            ],
            'status check is RED then the status is RED'                          => [
                'GREEN',
                'RED',
                'RED',
            ],
            'if both status check and check runs are GREEN then the status is GREEN' => [
                'GREEN',
                'GREEN',
                'GREEN',
            ],
        ];
    }

    private function mockIndependentResults(
        ?string $checkRunStatus,
        ?string $statusCheckStatus
    ): void {
        $prIdentifierArgument = Argument::that(
            function (PRIdentifier $PRIdentifier) {
                return $PRIdentifier->equals($PRIdentifier::fromString(self::PR_IDENTIFIER));
            }
        );
        $commitRefArgument = Argument::that(
            function (string $commitRef) {
                return self::COMMIT_REF === $commitRef;
            }
        );
        $this->getCheckRunStatus->fetch($prIdentifierArgument, $commitRefArgument)->willReturn(
            $checkRunStatus
        );
        $this->getStatusCheckStatus->fetch($prIdentifierArgument, $commitRefArgument)->willReturn(
            $statusCheckStatus
        );
    }

    private function getCIStatus(): string
    {
        $actualCIStatus = $this->getCIStatus->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER), self::COMMIT_REF);

        return $actualCIStatus;
    }
}
