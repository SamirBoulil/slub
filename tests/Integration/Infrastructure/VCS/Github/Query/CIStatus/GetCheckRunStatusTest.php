<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckRunStatus;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Tests\WebTestCase;

class GetCheckRunStatusTest extends WebTestCase
{
    private const PR_COMMIT_REF = 'pr_commit_ref';
    private const SUPPORTED_CI_CHECK_1 = 'supported_1';
    private const SUPPORTED_CI_CHECK_2 = 'supported_2';
    private const SUPPORTED_CI_CHECK_3 = 'supported_3';
    private const NOT_SUPPORTED_CI_CHECK = 'unsupported';
    private const BUILD_LINK = 'http://my-ci.com/build/123';

    /** @var GetCheckRunStatus */
    private $getCheckRunStatus;

    /** @var ObjectProphecy|GithubAPIClient */
    private $githubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->githubAPIClient = $this->prophesize(GithubAPIClient::class);

        $this->getCheckRunStatus = new GetCheckRunStatus(
            $this->githubAPIClient->reveal(),
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
        $uri = 'https://api.github.com/repos/SamirBoulil/slub/commits/'.self::PR_COMMIT_REF.'/check-runs';
        $repositoryIdentifier = 'SamirBoulil/slub';
        $this->githubAPIClient->get(
            $uri,
            ['headers' => GithubAPIHelper::acceptPreviewEndpointsHeader()],
            $repositoryIdentifier
        )->willReturn(new Response(200, [], (string)json_encode($ciCheckRuns)));

        $actualCheckStatus = $this->getCheckRunStatus->fetch(
            PRIdentifier::fromString('SamirBoulil/slub/36'),
            self::PR_COMMIT_REF
        );

        $this->assertEquals($expectedCIStatus, $actualCheckStatus->status);
        $this->assertEquals($expectedBuildLink, $actualCheckStatus->buildLink);
    }

    public function checkRunsExamples(): array
    {
        return [
            'CI Checks not supported' => [
                [
                    'check_runs' => [
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'completed'],
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'failure'],
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'completed'],
                    ],
                ],
                'PENDING',
                '',
            ],
            'Supported CI Checks not run' => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'neutral', 'status' => 'pending'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'neutral', 'status' => 'pending'],
                        ['name' => self::NOT_SUPPORTED_CI_CHECK, 'conclusion' => 'success', 'status' => 'completed'],
                    ],
                ],
                'PENDING',
                '',
            ],
            'Multiple CI checks Green' => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'success', 'status' => 'completed'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'success', 'status' => 'completed'],
                    ],
                ],
                'GREEN',
                '',
            ],
            'Multiple CI checks Red' => [
                [
                    'check_runs' => [
                        [
                            'name' => self::SUPPORTED_CI_CHECK_1,
                            'conclusion' => 'failure',
                            'status' => 'completed',
                            'details_url' => self::BUILD_LINK,
                        ],
                        [
                            'name' => self::SUPPORTED_CI_CHECK_2,
                            'conclusion' => 'failure',
                            'status' => 'completed',
                            'details_url' => self::BUILD_LINK,
                        ],
                    ],
                ],
                'RED',
                self::BUILD_LINK,
            ],
            'Multiple CI checks Pending' => [
                [
                    'check_runs' => [
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'neutral', 'status' => 'pending'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'neutral', 'status' => 'pending'],
                    ],
                ],
                'PENDING',
                '',
            ],
            'Mixed CI checks statuses: red' => [
                [
                    'check_runs' => [
                        [
                            'name' => self::NOT_SUPPORTED_CI_CHECK,
                            'conclusion' => 'failure',
                            'status' => 'completed',
                            'details_url' => self::BUILD_LINK,
                        ],
                        ['name' => self::SUPPORTED_CI_CHECK_1, 'conclusion' => 'success', 'status' => 'completed'],
                        ['name' => self::SUPPORTED_CI_CHECK_2, 'conclusion' => 'neutral', 'status' => 'pending'],
                    ],
                ],
                'RED',
                self::BUILD_LINK,
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
                '',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_malformed(): void
    {
        $this->githubAPIClient->get(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(200, [], (string)'{'));
        $this->expectException(\RuntimeException::class);

        $this->getCheckRunStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), 'pr_ref');
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_not_successfull(): void
    {
        $this->githubAPIClient->get(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(400, [], '{}'));
        $this->expectException(\RuntimeException::class);

        $this->getCheckRunStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), 'pr_ref');
    }
}
