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
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetStatusChecksStatus;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Tests\WebTestCase;

class GetStatusChecksStatusTest extends WebTestCase
{
    private const PR_COMMIT_REF = 'pr_commit_ref';

    private GetStatusChecksStatus $getStatusCheckStatus;

    private GithubAPIClientInterface|ObjectProphecy $githubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->githubAPIClient = $this->prophesize(GithubAPIClient::class);
        $this->getStatusCheckStatus = new GetStatusChecksStatus(
            $this->githubAPIClient->reveal(),
            new NullLogger(),
            'https://api.github.com'
        );
    }

    public function test_it_fetches_check_status_from_check_runs()
    {
        $uri = 'https://api.github.com/repos/SamirBoulil/slub/statuses/'.self::PR_COMMIT_REF;
        $repositoryIdentifier = 'SamirBoulil/slub';
        $expectedSuccessStatusCheck = 'check success';
        $expectedNeutralStatusCheck = 'check neutral';
        $expectedFailedStatusCheck = 'check faileds';
        $expectedFailedBuildLink = 'url to failed step';
        $this->githubAPIClient->get(
            $uri,
            ['headers' => GithubAPIHelper::acceptPreviewEndpointsHeader()],
            $repositoryIdentifier
        )->willReturn(
            new Response(
                200, [], (string)json_encode(
                [
                        [
                            'context' => $expectedSuccessStatusCheck,
                            'state' => 'success',
                            'updated_at' => '2020-03-31T10:26:20Z',
                        ],
                        [
                            'context' => $expectedNeutralStatusCheck,
                            'state' => 'neutral',
                            'updated_at' => '2020-03-31T10:26:20Z',
                        ],
                        [
                            'context' => $expectedFailedStatusCheck,
                            'state' => 'failure',
                            'target_url' => $expectedFailedBuildLink,
                            'updated_at' => '2020-03-31T10:26:20Z',
                        ],
                ],
                JSON_THROW_ON_ERROR
            ))
        );

        $actualCheckStatuses = $this->getStatusCheckStatus->fetch(
            PRIdentifier::fromString('SamirBoulil/slub/36'),
            self::PR_COMMIT_REF
        );

        $this->assertCount(3, $actualCheckStatuses);
        $this->assertEquals(CheckStatus::green($expectedSuccessStatusCheck), $actualCheckStatuses[0]);
        $this->assertEquals(CheckStatus::pending($expectedNeutralStatusCheck), $actualCheckStatuses[1]);
        $this->assertEquals(CheckStatus::red($expectedFailedStatusCheck, $expectedFailedBuildLink), $actualCheckStatuses[2]);
    }

    // TODO: Add tests for sorting and uniquing

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
