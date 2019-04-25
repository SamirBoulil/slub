<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Query;

use GuzzleHttp\Psr7\Response;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetPRDetails;
use Tests\Integration\Infrastructure\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class GetCIStatusTest extends WebTestCase
{
    private const AUTH_TOKEN = 'TOKEN';
    private const PR_COMMIT_REF = 'pr_commit_ref';
    private const SUPPORTED_CI_CHECK_1 = 'supported_1';
    private const SUPPORTED_CI_CHECK_2 = 'supported_2';
    private const SUPPORTED_CI_CHECK_3 = 'supported_3';
    private const NOT_SUPPORTED_CI_CHECK = 'unsupported';

    /** @var GetCIStatus*/
    private $getCIStatus;

    /** @var GuzzleSpy */
    private $requestSpy;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestSpy = new GuzzleSpy();
        $this->getCIStatus = new GetCIStatus(
            $this->requestSpy->client(),
            self::AUTH_TOKEN,
            implode(',', [self::SUPPORTED_CI_CHECK_1, self::SUPPORTED_CI_CHECK_2, self::SUPPORTED_CI_CHECK_3])
        );
    }

    /**
     * @test
     */
    public function it_uses_the_check_suite_status_to_determine_if_it_is_red(): void
    {
        $ciCheckSuite = [
            'check_suites' => [
                ['conclusion' => 'failure', 'status' => 'completed'],
            ]
        ];
        $this->requestSpy->stubResponse(new Response(200, [], (string) json_encode($ciCheckSuite)));

        $actualCIStatus = $this->getCIStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), self::PR_COMMIT_REF);

        $this->assertEquals('RED', $actualCIStatus);
        $generatedRequest = $this->requestSpy->getRequest();
        $this->requestSpy->assertMethod('GET', $generatedRequest);
        $this->requestSpy->assertURI('/repos/SamirBoulil/slub/commits/' . self::PR_COMMIT_REF . '/check-suites', $generatedRequest);
        $this->requestSpy->assertAuthToken(self::AUTH_TOKEN, $generatedRequest);
        $this->requestSpy->assertContentEmpty($generatedRequest);
    }

    /**
     * @test
     * @dataProvider checkRunsExamples
     */
    public function it_uses_the_check_runs_status_when_the_check_suite_is_not_failed(array $ciCheckRuns, string $expectedCIStatus): void
    {
        $this->setCheckSuitePending();
        $this->requestSpy->stubResponse(new Response(200, [], (string) json_encode($ciCheckRuns)));

        $actualCIStatus = $this->getCIStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), self::PR_COMMIT_REF);

        $this->assertEquals($expectedCIStatus, $actualCIStatus);
        $generatedRequest = $this->requestSpy->getRequest();
        $this->requestSpy->assertMethod('GET', $generatedRequest);
        $this->requestSpy->assertURI('/repos/SamirBoulil/slub/commits/' . self::PR_COMMIT_REF . '/check-runs', $generatedRequest);
        $this->requestSpy->assertAuthToken(self::AUTH_TOKEN, $generatedRequest);
        $this->requestSpy->assertContentEmpty($generatedRequest);
    }

    public function checkRunsExamples(): array
    {
        return [
            'CI Checks not supported' => [
                [
                    'check_runs' => [
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'completed'],
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'failure'],
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'completed']
                    ],
                ],
                'PENDING'
            ],
            'Supported CI Checks not run' => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'neutral', 'status' => 'pending'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'neutral', 'status' => 'pending'],
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'completed']
                    ],
                ],
                'PENDING'
            ],
            'Multiple CI checks Green' => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'success', 'status' => 'completed'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'success', 'status' => 'completed'],
                    ],
                ],
                'GREEN'
            ],
            'Multiple CI checks Red' => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'failure', 'status' => 'completed'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'failure', 'status' => 'completed'],
                    ],
                ],
                'RED'
            ],
            'Mixed CI checks statuses' => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'failure', 'status' => 'completed'],
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'success', 'status' => 'completed'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'neutral', 'status' => 'pending'],
                    ],
                ],
                'RED'
            ]
        ];
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_malformed(): void
    {
        $this->requestSpy->stubResponse(new Response(200, [], '{'));
        $this->expectException(\RuntimeException::class);

        $this->getCIStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), 'pr_ref');
    }

    private function setCheckSuitePending(): void
    {
        $ciCheckSuite = [
            'check_suites' => [
                ['conclusion' => 'pending', 'status' => 'completed'],
            ]
        ];
        $this->requestSpy->stubResponse(new Response(200, [], (string) json_encode($ciCheckSuite)));
    }
}
