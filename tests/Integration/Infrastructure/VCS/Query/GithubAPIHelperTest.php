<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Query;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

class GithubAPIHelperTest extends TestCase
{
    /**
     * @test
     */
    public function it_breaks_out_a_pr_identifier()
    {
        $this->assertEquals(
            GithubAPIHelper::breakoutPRIdentifier(PRIdentifier::fromString('SamirBoulil/slub/36')),
            [
                'SamirBoulil',
                'slub',
                '36'
            ]
        );
    }

    /**
     * @test
     */
    public function it_generates_a_authorization_header_for_a_token()
    {
        $expectedToken = 'token_123';
        $this->assertEquals(
            GithubAPIHelper::authorizationHeader($expectedToken),
            ['Authorization' => 'token ' . $expectedToken]
        );
    }

    /**
     * @test
     */
    public function it_generates_a_accept_header_for_preview_endpoints()
    {
        $this->assertEquals(
            GithubAPIHelper::acceptPreviewEndpointsHeader(),
            ['Accept' => 'application/vnd.github.antiope-preview+json']
        );
    }
}
