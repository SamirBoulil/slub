<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Query\CIStatus;

use GuzzleHttp\Psr7\Response;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetStatusChecksStatus;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;
use Tests\Integration\Infrastructure\VCS\Query\GuzzleSpy;
use Tests\WebTestCase;

class GetStatusCheckStatusTest extends WebTestCase
{
    private const AUTH_TOKEN = 'TOKEN';
    private const PR_COMMIT_REF = 'pr_commit_ref';
    private const SUPPORTED_CI_STATUS_1 = 'supported_1';
    private const SUPPORTED_CI_STATUS_2 = 'supported_2';
    private const SUPPORTED_CI_CHECK_3 = 'supported_3';
    private const NOT_SUPPORTED_CI_STATUS = 'unsupported';

    /** @var GetStatusChecksStatus */
    private $getStatusCheckStatus;

    /** @var GuzzleSpy */
    private $requestSpy;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestSpy = new GuzzleSpy();
        $this->getStatusCheckStatus = new GetStatusChecksStatus(
            $this->requestSpy->client(),
            self::AUTH_TOKEN,
            implode(',', [self::SUPPORTED_CI_STATUS_1, self::SUPPORTED_CI_STATUS_2, self::SUPPORTED_CI_CHECK_3]),
            'api.github.com'
        );
    }

    /**
     * @test
     * @dataProvider ciStatusesExamples
     */
    public function it_uses_the_ci_statuses_when_the_check_suite_is_not_failed(
        array $ciStatuses,
        string $expectedCIStatus
    ): void {
        $this->requestSpy->stubResponse(new Response(200, [], (string)json_encode($ciStatuses)));

        $actualCIStatus = $this->getStatusCheckStatus->fetch(
            PRIdentifier::fromString('SamirBoulil/slub/36'),
            self::PR_COMMIT_REF
        );

        $this->assertEquals($expectedCIStatus, $actualCIStatus);
        $generatedRequest = $this->requestSpy->getRequest();
        $this->requestSpy->assertMethod('GET', $generatedRequest);
        $this->requestSpy->assertURI(
            '/repos/SamirBoulil/slub/statuses/' . self::PR_COMMIT_REF,
            $generatedRequest
        );
        $this->requestSpy->assertAuthToken(self::AUTH_TOKEN, $generatedRequest);
        $this->requestSpy->assertContentEmpty($generatedRequest);
    }

    public function ciStatusesExamples(): array
    {
        return [
            'Status not supported'     => [
                [
                    ['context' => self::NOT_SUPPORTED_CI_STATUS, 'state' => 'success'],
                    ['context' => self::NOT_SUPPORTED_CI_STATUS, 'state' => 'success'],
                    ['context' => self::NOT_SUPPORTED_CI_STATUS, 'state' => 'success']
                ],
                'PENDING'
            ],
            'Supported status not run' => [
                [
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'neutral'],
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'neutral'],
                    ['context' => self::NOT_SUPPORTED_CI_STATUS, 'state' => 'success']
                ],
                'PENDING'
            ],
            'Multiple Status Green'    => [
                [
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'success'],
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'success'],
                ],
                'GREEN'
            ],
            'Multiple status Red'      => [
                [
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'failure'],
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'failure'],
                ],
                'RED'
            ],
            'Multiple status Pending'  => [
                [
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'pending'],
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'pending'],
                ],
                'PENDING'
            ],
            'Mixed statuses: red'      => [
                [
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'failure'],
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'success'],
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'neutral'],
                ],
                'RED'
            ],
            'Mixed statuses: green'    => [
                [
                    ['context' => self::SUPPORTED_CI_STATUS_2, 'state' => 'success'],
                    ['context' => self::SUPPORTED_CI_STATUS_1, 'state' => 'neutral'],
                    ['context' => self::NOT_SUPPORTED_CI_STATUS, 'state' => 'neutral'],
                ],
                'GREEN'
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

        $this->getStatusCheckStatus->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'), 'pr_ref');
    }
}
