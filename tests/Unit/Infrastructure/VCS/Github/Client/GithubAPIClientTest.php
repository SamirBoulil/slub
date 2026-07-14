<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\Client;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlAppInstallationRepository;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;
use Slub\Infrastructure\VCS\Github\Client\GithubAppInstallation;
use Slub\Infrastructure\VCS\Github\Client\RefreshAccessToken;
use Tests\Integration\Infrastructure\KernelTestCase;
use Tests\Integration\Infrastructure\VCS\Github\Query\GuzzleSpy;

class GithubAPIClientTest extends KernelTestCase
{
    private const REPOSITORY_IDENTIFIER = 'samirboulil/slub';
    private const ACCESS_TOKEN = '1234cZA12';
    public const INSTALLATION_ID = '1234';

    private GuzzleSpy $requestSpy;

    private ObjectProphecy|RefreshAccessToken $refreshAccessToken;

    private ObjectProphecy|SqlAppInstallationRepository $sqlAppInstallationRepository;

    private GithubAPIClientInterface $githubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestSpy = new GuzzleSpy();
        $this->refreshAccessToken = $this->prophesize(RefreshAccessToken::class);
        $this->sqlAppInstallationRepository = $this->prophesize(SqlAppInstallationRepository::class);
        $this->githubAPIClient = $this->createGithubAPIClient(new NullLogger());
    }

    /** @test */
    public function it_fetches_the_access_token_to_call_the_github_api(): void
    {
        $url = 'https://github.com/some_endpoint';
        $responseContent = 'some_answer';
        $appInstallation = $this->appInstallation();

        $this->sqlAppInstallationRepository->getBy(self::REPOSITORY_IDENTIFIER)
             ->willReturn($appInstallation);
        $this->requestSpy->stubResponse(new Response(200, [], $responseContent));

        $response = $this->githubAPIClient->get($url, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals($responseContent, $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());

        $request = $this->requestSpy->getRequest();
        $this->requestSpy->assertURI('/some_endpoint', $request);
        $this->requestSpy->assertContentEmpty($request);
        $this->requestSpy->assertAuthToken('1234cZA12', $request);
    }

    /** @test */
    public function it_refreshes_and_saves_the_access_token_when_it_expires_before_calling_the_github_api(): void
    {
        $url = 'https://github.com/some_endpoint';
        $responseContent = 'some_answer';
        $appInstallation = $this->appInstallation();
        $newAccessToken = 'new_access_token';

        $this->sqlAppInstallationRepository->getBy(self::REPOSITORY_IDENTIFIER)
             ->willReturn($appInstallation);

        // First, unauthorized then authorized
        $this->requestSpy->stubResponse(new Response(401, [], ''));
        $this->requestSpy->stubResponse(new Response(200, [], $responseContent));

        $this->refreshAccessToken->fetch(self::INSTALLATION_ID)->willReturn($newAccessToken);
        $this->sqlAppInstallationRepository->save(
            Argument::that(
                fn (GithubAppInstallation $appInstallation) => $appInstallation->accessToken === $newAccessToken
                    && self::INSTALLATION_ID === $appInstallation->installationId
                    && self::REPOSITORY_IDENTIFIER === $appInstallation->repositoryIdentifier
            )
        )->shouldBeCalled();
        $response = $this->githubAPIClient->get($url, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals($responseContent, $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());

        $request = $this->requestSpy->getRequest();
        $this->requestSpy->assertURI('/some_endpoint', $request);
        $this->requestSpy->assertContentEmpty($request);
        $this->requestSpy->assertAuthToken($newAccessToken, $request);
    }

    /** @test */
    public function it_calls_the_github_api_with_timeouts_and_without_throwing_on_http_errors(): void
    {
        $this->sqlAppInstallationRepository->getBy(self::REPOSITORY_IDENTIFIER)
             ->willReturn($this->appInstallation());
        $this->requestSpy->stubResponse(new Response(200, [], 'some_answer'));

        $this->githubAPIClient->get('https://github.com/some_endpoint', [], self::REPOSITORY_IDENTIFIER);

        $requestOptions = $this->requestSpy->getRequestOptions();
        self::assertFalse($requestOptions['http_errors']);
        self::assertEquals(5, $requestOptions['connect_timeout']);
        self::assertEquals(10, $requestOptions['timeout']);
    }

    /** @test */
    public function it_logs_a_dedicated_error_when_the_github_rate_limit_is_hit(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $githubAPIClient = $this->createGithubAPIClient($logger->reveal());
        $this->sqlAppInstallationRepository->getBy(self::REPOSITORY_IDENTIFIER)
             ->willReturn($this->appInstallation());
        $rateLimitedResponseContent = '{"message": "API rate limit exceeded"}';
        $this->requestSpy->stubResponse(
            new Response(403, ['X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => '1767225600'], $rateLimitedResponseContent)
        );
        $logger->debug(Argument::cetera())->shouldBeCalled();
        $logger->error(Argument::containingString('RATE LIMIT'))->shouldBeCalled();
        $logger->warning(Argument::cetera())->shouldNotBeCalled();

        $response = $githubAPIClient->get('https://github.com/some_endpoint', [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(403, $response->getStatusCode());
        self::assertEquals($rateLimitedResponseContent, $response->getBody()->getContents());
    }

    /** @test */
    public function it_logs_a_dedicated_error_when_secondary_rate_limited_with_a_retry_after_header(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $githubAPIClient = $this->createGithubAPIClient($logger->reveal());
        $this->sqlAppInstallationRepository->getBy(self::REPOSITORY_IDENTIFIER)
             ->willReturn($this->appInstallation());
        $this->requestSpy->stubResponse(new Response(429, ['Retry-After' => '60'], ''));
        $logger->debug(Argument::cetera())->shouldBeCalled();
        $logger->error(Argument::containingString('RATE LIMIT'))->shouldBeCalled();
        $logger->warning(Argument::cetera())->shouldNotBeCalled();

        $response = $githubAPIClient->get('https://github.com/some_endpoint', [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(429, $response->getStatusCode());
    }

    /** @test */
    public function it_logs_a_warning_when_the_github_api_returns_an_unexpected_status(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $githubAPIClient = $this->createGithubAPIClient($logger->reveal());
        $this->sqlAppInstallationRepository->getBy(self::REPOSITORY_IDENTIFIER)
             ->willReturn($this->appInstallation());
        $responseContent = '{"message": "Server Error"}';
        $this->requestSpy->stubResponse(new Response(500, [], $responseContent));
        $logger->debug(Argument::cetera())->shouldBeCalled();
        $logger->warning(Argument::containingString('unexpected status'))->shouldBeCalled();
        $logger->error(Argument::cetera())->shouldNotBeCalled();

        $response = $githubAPIClient->get('https://github.com/some_endpoint', [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(500, $response->getStatusCode());
        self::assertEquals($responseContent, $response->getBody()->getContents());
    }

    private function createGithubAPIClient(LoggerInterface $logger): GithubAPIClient
    {
        return new GithubAPIClient(
            $this->refreshAccessToken->reveal(),
            $this->sqlAppInstallationRepository->reveal(),
            $this->requestSpy->client(),
            $logger
        );
    }

    private function appInstallation(): GithubAppInstallation
    {
        $appInstallation = new GithubAppInstallation();
        $appInstallation->repositoryIdentifier = self::REPOSITORY_IDENTIFIER;
        $appInstallation->installationId = self::INSTALLATION_ID;
        $appInstallation->accessToken = self::ACCESS_TOKEN;

        return $appInstallation;
    }
}
