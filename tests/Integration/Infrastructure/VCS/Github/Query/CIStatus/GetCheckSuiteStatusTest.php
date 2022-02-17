<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckSuiteStatus;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Tests\WebTestCase;

class GetCheckSuiteStatusTest extends WebTestCase
{
    private const PR_COMMIT_REF = 'pr_commit_ref';
    private const SUPPORTED_CI_CHECK_1 = 'supported_1';
    private const SUPPORTED_CI_CHECK_2 = 'supported_2';
    private const SUPPORTED_CI_CHECK_3 = 'supported_3';
    private const BUILD_LINK = 'http://my-ci.com/build/123';

    private GetCheckSuiteStatus $getCheckSuiteStatus;

    private GithubAPIClientInterface|ObjectProphecy $githubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->githubAPIClient = $this->prophesize(GithubAPIClient::class);

        $this->getCheckSuiteStatus = new GetCheckSuiteStatus(
            $this->githubAPIClient->reveal(),
            implode(',', [self::SUPPORTED_CI_CHECK_1, self::SUPPORTED_CI_CHECK_2, self::SUPPORTED_CI_CHECK_3]),
            'https://api.github.com'
        );
    }

    /**
     * @test
     * @dataProvider checkSuiteExample
     */
    public function it_fetches_the_check_suite_status(array $checkSuite, string $expectedCIStatus, string $expectedBuildLink): void
    {
        $uri = 'https://api.github.com/repos/SamirBoulil/slub/commits/'.self::PR_COMMIT_REF.'/check-suites';
        $repositoryIdentifier = 'SamirBoulil/slub';
        $this->githubAPIClient->get(
            $uri,
            ['headers' => GithubAPIHelper::acceptPreviewEndpointsHeader()],
            $repositoryIdentifier
        )->willReturn(new Response(200, [], (string) json_encode($checkSuite)));

        $actualCheckStatus = $this->getCheckSuiteStatus->fetch(
            PRIdentifier::fromString('SamirBoulil/slub/36'),
            self::PR_COMMIT_REF
        );

        self::assertEquals($expectedCIStatus, $actualCheckStatus->status);
        self::assertEquals($expectedBuildLink, $actualCheckStatus->buildLink);
    }

    public function checkSuiteExample(): array
    {
        return [
            'Check suite is failed' => [
                ['check_suites' => [['conclusion' => 'failure', 'status' => 'completed', 'details_url' => self::BUILD_LINK]]],
                'RED',
                self::BUILD_LINK,
            ],
            'Check suite is green' => [
                ['check_suites' => [['conclusion' => 'success', 'status' => 'completed']]],
                'GREEN',
                '',
            ],
            'Check suite is pending' => [
                ['check_suites' => [['conclusion' => 'in queue', 'status' => null]]],
                'PENDING',
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

        $this->getCheckSuiteStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), 'pr_ref');
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_not_successfull(): void
    {
        $this->githubAPIClient->get(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(400, [], '{}'));
        $this->expectException(\RuntimeException::class);

        $this->getCheckSuiteStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), 'pr_ref');
    }
}
