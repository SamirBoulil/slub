<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckRunStatus;
use Tests\Integration\Infrastructure\VCS\Github\Query\GuzzleSpy;
use Tests\WebTestCase;

class GetCheckRunStatusTest extends WebTestCase
{
    private const AUTH_TOKEN = 'TOKEN';
    private const PR_COMMIT_REF = 'pr_commit_ref';
    private const SUPPORTED_CI_CHECK_1 = 'supported_1';
    private const SUPPORTED_CI_CHECK_2 = 'supported_2';
    private const SUPPORTED_CI_CHECK_3 = 'supported_3';
    private const NOT_SUPPORTED_CI_CHECK = 'unsupported';
    private const BUILD_LINK = 'http://my-ci.com/build/123';

    /** @var GetCheckRunStatus */
    private $getCheckRunStatus;

    /** @var GuzzleSpy */
    private $requestSpy;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestSpy = new GuzzleSpy();
        $this->getCheckRunStatus = new GetCheckRunStatus(
            $this->requestSpy->client(),
            self::AUTH_TOKEN,
            implode(',', [self::SUPPORTED_CI_CHECK_1, self::SUPPORTED_CI_CHECK_2, self::SUPPORTED_CI_CHECK_3]),
            'https://api.github.com',
            new NullLogger()
        );
    }

    /**
     * @test
     * @dataProvider checkRunsExamples
     */
    public function it_uses_the_check_runs_status_when_the_check_suite_is_not_failed(
        array $ciCheckRuns,
        string $expectedCIStatus,
        string $expectedBuildLink
    ): void {
        $this->requestSpy->stubResponse(new Response(200, [], (string) json_encode($ciCheckRuns)));

        $actualCheckStatus = $this->getCheckRunStatus->fetch(
            PRIdentifier::fromString('SamirBoulil/slub/36'),
            self::PR_COMMIT_REF
        );

        $this->assertEquals($expectedCIStatus, $actualCheckStatus->status);
        $this->assertEquals($expectedBuildLink, $actualCheckStatus->buildLink);
        $generatedRequest = $this->requestSpy->getRequest();
        $this->requestSpy->assertMethod('GET', $generatedRequest);
        $this->requestSpy->assertURI(
            '/repos/SamirBoulil/slub/commits/' . self::PR_COMMIT_REF . '/check-runs',
            $generatedRequest
        );
        $this->requestSpy->assertAuthToken(self::AUTH_TOKEN, $generatedRequest);
        $this->requestSpy->assertContentEmpty($generatedRequest);
    }

    public function checkRunsExamples(): array
    {
        return [
            'CI Checks not supported'         => [
                [
                    'check_runs' => [
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'completed'],
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'failure'],
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'completed'],
                    ],
                ],
                'PENDING',
                ''
            ],
            'Supported CI Checks not run'     => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'neutral', 'status' => 'pending'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'neutral', 'status' => 'pending'],
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'completed'],
                    ],
                ],
                'PENDING',
                ''
            ],
            'Multiple CI checks Green'        => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'success', 'status' => 'completed'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'success', 'status' => 'completed'],
                    ],
                ],
                'GREEN',
                ''
            ],
            'Multiple CI checks Red'          => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'failure', 'status' => 'completed', 'details_url' => self::BUILD_LINK],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'failure', 'status' => 'completed', 'details_url' => self::BUILD_LINK],
                    ],
                ],
                'RED',
                self::BUILD_LINK
            ],
            'Multiple CI checks Pending'      => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'neutral', 'status' => 'pending'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'neutral', 'status' => 'pending'],
                    ],
                ],
                'PENDING',
                ''
            ],
            'Mixed CI checks statuses: red'   => [
                [
                    'check_runs' => [
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'failure', 'status' => 'completed', 'details_url' => self::BUILD_LINK],
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'success', 'status' => 'completed'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'neutral', 'status' => 'pending'],
                    ],
                ],
                'RED',
                self::BUILD_LINK
            ],
            'Mixed CI checks statuses: green' => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'success', 'status' => 'completed'],
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'neutral', 'status' => 'pending'],
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'neutral', 'status' => 'pending'],
                    ],
                ],
                'GREEN',
                ''
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_malformed(): void
    {
        $this->requestSpy->stubResponse(new Response(200, [], '{'));
        $this->expectException(\RuntimeException::class);

        $this->getCheckRunStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), 'pr_ref');
    }
}
