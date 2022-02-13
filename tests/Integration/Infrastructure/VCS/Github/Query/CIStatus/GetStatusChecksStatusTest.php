<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetStatusChecksStatus;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Tests\WebTestCase;

class GetStatusChecksStatusTest extends WebTestCase
{
    private const PR_COMMIT_REF = 'pr_commit_ref';
    private const SUPPORTED_CI_STATUS_1 = 'supported_1';
    private const SUPPORTED_CI_STATUS_2 = 'supported_2';
    private const SUPPORTED_CI_CHECK_3 = 'supported_3';
    private const NOT_SUPPORTED_CI_STATUS = 'unsupported';
    private const BUILD_LINK = 'http://my-ci.com/build/123';

    private GetStatusChecksStatus $getStatusCheckStatus;

    private GithubAPIClient|ObjectProphecy $githubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->githubAPIClient = $this->prophesize(GithubAPIClient::class);
        $this->getStatusCheckStatus = new GetStatusChecksStatus(
            $this->githubAPIClient->reveal(),
            implode(',', [self::SUPPORTED_CI_STATUS_1, self::SUPPORTED_CI_STATUS_2, self::SUPPORTED_CI_CHECK_3]),
            'https://api.github.com',
            new NullLogger()
        );
    }

    /**
     * @test
     * @dataProvider ciStatusesExamples
     */
    public function it_fetches_and_deducts_the_ci_status_of_ci_checks(
        array $ciStatuses,
        string $expectedCIStatus,
        string $expectedBuildLink
    ): void {
        $uri = 'https://api.github.com/repos/SamirBoulil/slub/statuses/'.self::PR_COMMIT_REF;
        $repositoryIdentifier = 'SamirBoulil/slub';
        $this->githubAPIClient->get(
            $uri,
            ['headers' => GithubAPIHelper::acceptPreviewEndpointsHeader()],
            $repositoryIdentifier
        )->willReturn(new Response(200, [], (string) json_encode($ciStatuses)));

        $actualCIStatus = $this->getStatusCheckStatus->fetch(
            PRIdentifier::fromString('SamirBoulil/slub/36'),
            self::PR_COMMIT_REF
        );

        self::assertEquals($expectedCIStatus, $actualCIStatus->status);
        self::assertEquals($expectedBuildLink, $actualCIStatus->buildLink);
    }

    /**
     * @test
     */
    public function it_uses_the_ci_statuses_when_the_check_suite_is_not_failed(
    ): void {
        $day = '2020-03-30T10:26:20Z';
        $tomorrow = '2020-03-31T10:26:20Z';
        $ciStatuses = [
            ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'failure', 'updated_at' => $day],
            ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'success', 'updated_at' => $tomorrow],
        ];
        $this->githubAPIClient->get(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(200, [], (string) json_encode($ciStatuses)));

        $actualCIStatus = $this->getStatusCheckStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), self::PR_COMMIT_REF);

        self::assertEquals('GREEN', $actualCIStatus->status);
    }

    public function ciStatusesExamples(): array
    {
        return [
            'Status not supported' => [
                [
                    ['context' => self::NOT_SUPPORTED_CI_STATUS, 'state' => 'success', 'updated_at' => '2020-03-31T10:26:20Z'],
                    ['context' => self::NOT_SUPPORTED_CI_STATUS, 'state' => 'success', 'updated_at' => '2020-03-31T10:26:20Z'],
                    ['context' => self::NOT_SUPPORTED_CI_STATUS, 'state' => 'success', 'updated_at' => '2020-03-31T10:26:20Z'],
                ],
                'PENDING',
                '',
            ],
            'Supported status not run' => [
                [
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'neutral', 'updated_at' => '2020-03-31T10:26:20Z'],
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'neutral', 'updated_at' => '2020-03-31T10:26:20Z'],
                    ['context' => self::NOT_SUPPORTED_CI_STATUS, 'state' => 'success', 'updated_at' => '2020-03-31T10:26:20Z'],
                ],
                'PENDING',
                '',
            ],
            'Multiple Status Green' => [
                [
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'success', 'updated_at' => '2020-03-31T10:26:20Z'],
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'success', 'updated_at' => '2020-03-31T10:26:20Z'],
                ],
                'GREEN',
                '',
            ],
            'Multiple status Red' => [
                [
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'failure', 'target_url' => self::BUILD_LINK, 'updated_at' => '2020-03-31T10:26:20Z'],
                    ['context' => self::SUPPORTED_CI_STATUS_2,
                     'state' => 'failure',
                     'target_url' => 'http://my-ci.com/build/456',
                     'updated_at' => '2020-03-31T10:26:20Z',
                    ],
                ],
                'RED',
                self::BUILD_LINK,
            ],
            'Multiple status Pending' => [
                [
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'pending', 'updated_at' => '2020-03-31T10:26:20Z'],
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'pending', 'updated_at' => '2020-03-31T10:26:20Z'],
                ],
                'PENDING',
                '',
            ],
            'Mixed statuses: red' => [
                [
                    ['context' => self::NOT_SUPPORTED_CI_STATUS, 'state' => 'failure', 'target_url' => self::BUILD_LINK, 'updated_at' => '2020-03-31T10:26:20Z'],
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'success', 'updated_at' => '2020-03-31T10:26:20Z'],
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'neutral', 'updated_at' => '2020-03-31T10:26:20Z'],
                ],
                'RED',
                self::BUILD_LINK,
            ],
            'Mixed statuses: green' => [
                [
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'success', 'updated_at' => '2020-03-31T10:26:20Z'],
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'neutral', 'updated_at' => '2020-03-31T10:26:20Z'],
                    ['context' => self::NOT_SUPPORTED_CI_STATUS, 'state' => 'neutral', 'updated_at' => '2020-03-31T10:26:20Z'],
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
            ->willReturn(new Response(200, [], (string) '{'));
        $this->expectException(\RuntimeException::class);

        $this->getStatusCheckStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), 'pr_ref');
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_not_successfull(): void
    {
        $this->githubAPIClient->get(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(400, [], '{}'));
        $this->expectException(\RuntimeException::class);

        $this->getStatusCheckStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), 'pr_ref');
    }
}
