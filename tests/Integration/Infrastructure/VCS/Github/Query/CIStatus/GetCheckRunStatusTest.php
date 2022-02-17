<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckRunStatus;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Tests\WebTestCase;

class GetCheckRunStatusTest extends WebTestCase
{
    private const PR_COMMIT_REF = 'pr_commit_ref';
    private const SUPPORTED_CI_CHECK_1 = 'supported_1';
    private const SUPPORTED_CI_CHECK_2 = 'supported_2';
    private const SUPPORTED_CI_CHECK_3 = 'supported_3';
    private const NOT_SUPPORTED_CI_CHECK_1 = 'unsupported_1';
    private const NOT_SUPPORTED_CI_CHECK_2 = 'unsupported_2';
    private const BUILD_LINK = 'http://my-ci.com/build/123';

    private GetCheckRunStatus $getCheckRunStatus;

    private GithubAPIClientInterface|ObjectProphecy $githubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->githubAPIClient = $this->prophesize(GithubAPIClient::class);

        $this->getCheckRunStatus = new GetCheckRunStatus(
            $this->githubAPIClient->reveal(),
            'https://api.github.com',
            new NullLogger()
        );
    }

    public function test_it_fetches_check_status_from_check_runs()
    {
        $uri = 'https://api.github.com/repos/SamirBoulil/slub/commits/'.self::PR_COMMIT_REF.'/check-runs';
        $repositoryIdentifier = 'SamirBoulil/slub';
        $expectedSuccessCheckName = 'check success';
        $expectedNeutralCheckName = 'check neutral';
        $expectedFailedCheckName = 'check faileds';
        $expectedFailedBuildLink = 'url to failed step';
        $this->githubAPIClient->get(
            $uri,
            ['headers' => GithubAPIHelper::acceptPreviewEndpointsHeader()],
            $repositoryIdentifier
        )->willReturn(
            new Response(
                200, [], (string)json_encode(
                [
                    'check_runs' => [
                        [
                            'name' => $expectedSuccessCheckName,
                            'conclusion' => 'success',
                        ],
                        [
                            'name' => $expectedNeutralCheckName,
                            'conclusion' => 'neutral',
                        ],
                        [
                            'name' => $expectedFailedCheckName,
                            'details_url' => $expectedFailedBuildLink,
                            'conclusion' => 'failure',
                        ],
                    ],
                ],
                JSON_THROW_ON_ERROR
            ))
        );

        $actualCheckStatuses = $this->getCheckRunStatus->fetch(
            PRIdentifier::fromString('SamirBoulil/slub/36'),
            self::PR_COMMIT_REF
        );

        $this->assertCount(3, $actualCheckStatuses);
        $this->assertEquals(CheckStatus::green($expectedSuccessCheckName), $actualCheckStatuses[0]);
        $this->assertEquals(CheckStatus::pending($expectedNeutralCheckName), $actualCheckStatuses[1]);
        $this->assertEquals(CheckStatus::red($expectedFailedCheckName, $expectedFailedBuildLink), $actualCheckStatuses[2]);
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
