<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

class GithubAPIHelperTest extends TestCase
{
    /**
     * @test
     */
    public function it_breaks_out_a_pr_identifier(): void
    {
        $this->assertEquals(
            GithubAPIHelper::breakoutPRIdentifier(PRIdentifier::fromString('SamirBoulil/slub/36')),
            [
                'SamirBoulil',
                'slub',
                '36',
            ]
        );
    }

    // Add test for PRUrl()
    // Add test for PRAPIUrl()
    // Add test for PRIdentifierFrom

    /**
     * @test
     */
    public function it_generates_a_authorization_header_for_a_token(): void
    {
        $expectedToken = 'token_123';
        self::assertEquals(
            GithubAPIHelper::authorizationHeader($expectedToken),
            ['Authorization' => 'token '.$expectedToken]
        );
    }

    /**
     * @test
     */
    public function it_generates_a_authorization_header_for_a_jwt(): void
    {
        $expectedToken = 'token_123';
        self::assertEquals(
            GithubAPIHelper::authorizationHeaderWithJWT($expectedToken),
            ['Authorization' => 'Bearer '.$expectedToken]
        );
    }

    /**
     * @test
     */
    public function it_generates_a_accept_header_for_preview_endpoints(): void
    {
        self::assertEquals(
            GithubAPIHelper::acceptPreviewEndpointsHeader(),
            ['Accept' => 'application/vnd.github.antiope-preview+json']
        );
    }

    /**
     * @test
     */
    public function it_generates_a_accept_header_for_machine_man_preview_endpoints(): void
    {
        self::assertEquals(
            GithubAPIHelper::acceptMachineManPreviewHeader(),
            ['Accept' => 'application/vnd.github.machine-man-preview+json']
        );
    }
}
