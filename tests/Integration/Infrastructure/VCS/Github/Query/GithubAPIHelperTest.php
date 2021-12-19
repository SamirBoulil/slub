<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

class GithubAPIHelperTest extends TestCase
{
    public function test_it_returns_the_repository_identifier_from_a_pr_identifier(): void
    {
        $PRIdentifier = PRIdentifier::fromString('SamirBoulil/slub/36');
        $this->assertEquals(
            'SamirBoulil/slub',
            GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier)
        );
    }

    public function test_it_returns_the_PR_number_from_a_pr_identifier(): void
    {
        $this->assertEquals('36', GithubAPIHelper::PRNumber(PRIdentifier::fromString('SamirBoulil/slub/36')));
    }

    public function test_it_returns_the_PR_url_from_a_pr_identifier(): void
    {
        $this->assertEquals(
            'https://github.com/SamirBoulil/slub/pull/36',
            GithubAPIHelper::PRUrl(PRIdentifier::fromString('SamirBoulil/slub/36'))
        );
    }

    public function test_it_returns_the_PR_api_url_from_a_pr_identifier(): void
    {
        $this->assertEquals(
            'https://api.github.com/repos/SamirBoulil/slub/pulls/36',
            GithubAPIHelper::PRAPIUrl(PRIdentifier::fromString('SamirBoulil/slub/36'))
        );
    }

    public function test_it_returns_a_pr_identifier_from(): void
    {
        $this->assertEquals(
            'SamirBoulil/slub/36',
            GithubAPIHelper::PRIdentifierFrom('SamirBoulil/slub', '36')->stringValue()
        );
    }

    public function test_it_generates_a_authorization_header_for_a_token(): void
    {
        $expectedToken = 'token_123';
        self::assertEquals(
            GithubAPIHelper::authorizationHeader($expectedToken),
            ['Authorization' => 'token '.$expectedToken]
        );
    }

    public function test_it_generates_a_authorization_header_for_a_jwt(): void
    {
        $expectedToken = 'token_123';
        self::assertEquals(
            GithubAPIHelper::authorizationHeaderWithJWT($expectedToken),
            ['Authorization' => 'Bearer '.$expectedToken]
        );
    }

    public function test_it_generates_a_accept_header_for_preview_endpoints(): void
    {
        self::assertEquals(
            GithubAPIHelper::acceptPreviewEndpointsHeader(),
            ['Accept' => 'application/vnd.github.antiope-preview+json']
        );
    }

    public function test_it_generates_a_accept_header_for_machine_man_preview_endpoints(): void
    {
        self::assertEquals(
            GithubAPIHelper::acceptMachineManPreviewHeader(),
            ['Accept' => 'application/vnd.github.machine-man-preview+json']
        );
    }
}
