<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Psr7\Response;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckSuiteStatus;
use Tests\Integration\Infrastructure\VCS\Github\Query\GuzzleSpy;
use Tests\WebTestCase;

class GetCheckSuiteStatusTest extends WebTestCase
{
    private const AUTH_TOKEN = 'TOKEN';
    private const PR_COMMIT_REF = 'pr_commit_ref';
    private const SUPPORTED_CI_CHECK_1 = 'supported_1';
    private const SUPPORTED_CI_CHECK_2 = 'supported_2';
    private const SUPPORTED_CI_CHECK_3 = 'supported_3';
    private const BUILD_LINK = 'http://my-ci.com/build/123';

    /** @var GetCheckSuiteStatus */
    private $getCheckSuiteStatus;

    /** @var GuzzleSpy */
    private $requestSpy;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestSpy = new GuzzleSpy();
        $this->getCheckSuiteStatus = new GetCheckSuiteStatus(
            $this->requestSpy->client(),
            self::AUTH_TOKEN,
            implode(',', [self::SUPPORTED_CI_CHECK_1, self::SUPPORTED_CI_CHECK_2, self::SUPPORTED_CI_CHECK_3])
        );
    }

    /**
     * @test
     * @dataProvider checkSuiteExample
     */
    public function it_fetches_the_check_suite_status(array $checkSuite, string $expectedCIStatus, string $expectedBuildLink): void
    {
        $this->requestSpy->stubResponse(new Response(200, [], (string)json_encode($checkSuite)));

        $actualCheckStatus = $this->getCheckSuiteStatus->fetch(
            PRIdentifier::fromString('SamirBoulil/slub/36'),
            self::PR_COMMIT_REF
        );

        $this->assertEquals($expectedCIStatus, $actualCheckStatus->status);
        $this->assertEquals($expectedBuildLink, $actualCheckStatus->buildLink);
        $generatedRequest = $this->requestSpy->getRequest();
        $this->requestSpy->assertMethod('GET', $generatedRequest);
        $this->requestSpy->assertURI(
            '/repos/SamirBoulil/slub/commits/' . self::PR_COMMIT_REF . '/check-suites',
            $generatedRequest
        );
        $this->requestSpy->assertAuthToken(self::AUTH_TOKEN, $generatedRequest);
        $this->requestSpy->assertContentEmpty($generatedRequest);
    }

    public function checkSuiteExample()
    {
        return [
            'Check suite is failed' => [
                ['check_suites' => [['conclusion' => 'failure', 'status' => 'completed', 'details_url' => self::BUILD_LINK]]],
                'RED',
                self::BUILD_LINK
            ],
            'Check suite is green'  => [
                ['check_suites' => [['conclusion' => 'success', 'status' => 'completed']]],
                'GREEN',
                ''
            ],
            'Check suite is pending'  => [
                ['check_suites' => [['conclusion' => 'in queue', 'status' => null]]],
                'PENDING',
                ''
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

        $this->getCheckSuiteStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), 'pr_ref');
    }
}
