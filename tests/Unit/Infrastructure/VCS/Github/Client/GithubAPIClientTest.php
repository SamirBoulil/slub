<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\Client;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlAppInstallationRepository;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlGithubAPIResponseCacheRepository;
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
    private const URL = 'https://api.github.com/repos/samirboulil/slub/pulls/10';
    private const ETAG = 'W/"a_nice_etag"';
    private const RESPONSE_BODY = '{"title": "A PR"}';

    private GuzzleSpy $requestSpy;

    private ObjectProphecy|RefreshAccessToken $refreshAccessToken;

    private ObjectProphecy|SqlAppInstallationRepository $sqlAppInstallationRepository;

    private ObjectProphecy|SqlGithubAPIResponseCacheRepository $responseCacheRepository;

    private GithubAPIClientInterface $githubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestSpy = new GuzzleSpy();
        $this->refreshAccessToken = $this->prophesize(RefreshAccessToken::class);
        $this->sqlAppInstallationRepository = $this->prophesize(SqlAppInstallationRepository::class);
        $this->responseCacheRepository = $this->prophesize(SqlGithubAPIResponseCacheRepository::class);
        $this->sqlAppInstallationRepository->getBy(self::REPOSITORY_IDENTIFIER)->willReturn($this->appInstallation());
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

    /** @test */
    public function it_calls_the_github_api_and_caches_the_response_when_it_is_not_cached_yet(): void
    {
        $this->responseCacheRepository->find(self::URL)->willReturn(null);
        $this->requestSpy->stubResponse(new Response(200, ['ETag' => self::ETAG], self::RESPONSE_BODY));
        $this->responseCacheRepository->save(self::URL, self::ETAG, self::RESPONSE_BODY)->shouldBeCalled();

        $response = $this->githubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(self::RESPONSE_BODY, $response->getBody()->getContents());
        self::assertFalse($this->requestSpy->getRequest()->hasHeader('If-None-Match'));
    }

    /** @test */
    public function it_serves_the_cached_response_when_github_says_it_has_not_changed(): void
    {
        $this->responseCacheRepository->find(self::URL)
            ->willReturn(['ETAG' => self::ETAG, 'RESPONSE_BODY' => self::RESPONSE_BODY]);
        $this->requestSpy->stubResponse(new Response(304, [], ''));
        $this->responseCacheRepository->save(Argument::cetera())->shouldNotBeCalled();
        $this->responseCacheRepository->touch(self::URL)->shouldBeCalled();

        $response = $this->githubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(self::RESPONSE_BODY, $response->getBody()->getContents());
        self::assertEquals(self::ETAG, $this->requestSpy->getRequest()->getHeaderLine('If-None-Match'));
    }

    /** @test */
    public function it_still_serves_the_cached_response_when_touching_it_fails(): void
    {
        $this->responseCacheRepository->find(self::URL)
            ->willReturn(['ETAG' => self::ETAG, 'RESPONSE_BODY' => self::RESPONSE_BODY]);
        $this->requestSpy->stubResponse(new Response(304, [], ''));
        $this->responseCacheRepository->touch(self::URL)->willThrow(new \RuntimeException('Cache is broken'));

        $response = $this->githubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(self::RESPONSE_BODY, $response->getBody()->getContents());
    }

    /** @test */
    public function it_updates_the_cached_response_when_it_has_changed(): void
    {
        $newEtag = 'W/"a_new_etag"';
        $newResponseBody = '{"title": "An updated PR"}';
        $this->responseCacheRepository->find(self::URL)
            ->willReturn(['ETAG' => self::ETAG, 'RESPONSE_BODY' => self::RESPONSE_BODY]);
        $this->requestSpy->stubResponse(new Response(200, ['ETag' => $newEtag], $newResponseBody));
        $this->responseCacheRepository->save(self::URL, $newEtag, $newResponseBody)->shouldBeCalled();

        $response = $this->githubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($newResponseBody, $response->getBody()->getContents());
    }

    /** @test */
    public function it_does_not_cache_responses_without_etag(): void
    {
        $this->responseCacheRepository->find(self::URL)->willReturn(null);
        $this->requestSpy->stubResponse(new Response(200, [], self::RESPONSE_BODY));
        $this->responseCacheRepository->save(Argument::cetera())->shouldNotBeCalled();

        $response = $this->githubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(self::RESPONSE_BODY, $response->getBody()->getContents());
    }

    /** @test */
    public function it_does_not_cache_error_responses(): void
    {
        $errorResponseBody = '{"message": "Server error"}';
        $this->responseCacheRepository->find(self::URL)->willReturn(null);
        $this->requestSpy->stubResponse(new Response(500, [], $errorResponseBody));
        $this->responseCacheRepository->save(Argument::cetera())->shouldNotBeCalled();

        $response = $this->githubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(500, $response->getStatusCode());
        self::assertEquals($errorResponseBody, $response->getBody()->getContents());
    }

    /** @test */
    public function it_refreshes_the_access_token_and_still_serves_the_cached_response(): void
    {
        $newAccessToken = 'new_access_token';
        $this->responseCacheRepository->find(self::URL)
            ->willReturn(['ETAG' => self::ETAG, 'RESPONSE_BODY' => self::RESPONSE_BODY]);
        $this->requestSpy->stubResponse(new Response(401, [], ''));
        $this->requestSpy->stubResponse(new Response(304, [], ''));
        $this->refreshAccessToken->fetch(self::INSTALLATION_ID)->willReturn($newAccessToken);
        $this->sqlAppInstallationRepository->save(Argument::type(GithubAppInstallation::class))->shouldBeCalled();
        $this->responseCacheRepository->touch(self::URL)->shouldBeCalled();

        $response = $this->githubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(self::RESPONSE_BODY, $response->getBody()->getContents());
        $lastRequest = $this->requestSpy->getRequest();
        self::assertEquals(self::ETAG, $lastRequest->getHeaderLine('If-None-Match'));
        $this->requestSpy->assertAuthToken($newAccessToken, $lastRequest);
    }

    /** @test */
    public function it_calls_the_github_api_without_revalidation_when_the_cache_read_fails(): void
    {
        $this->responseCacheRepository->find(self::URL)->willThrow(new \RuntimeException('Cache is broken'));
        $this->requestSpy->stubResponse(new Response(200, ['ETag' => self::ETAG], self::RESPONSE_BODY));
        $this->responseCacheRepository->save(self::URL, self::ETAG, self::RESPONSE_BODY)->shouldBeCalled();

        $response = $this->githubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(self::RESPONSE_BODY, $response->getBody()->getContents());
        self::assertFalse($this->requestSpy->getRequest()->hasHeader('If-None-Match'));
    }

    /** @test */
    public function it_still_returns_the_response_when_the_cache_write_fails(): void
    {
        $this->responseCacheRepository->find(self::URL)->willReturn(null);
        $this->requestSpy->stubResponse(new Response(200, ['ETag' => self::ETAG], self::RESPONSE_BODY));
        $this->responseCacheRepository->save(self::URL, self::ETAG, self::RESPONSE_BODY)
            ->willThrow(new \RuntimeException('Cache is broken'));

        $response = $this->githubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(self::RESPONSE_BODY, $response->getBody()->getContents());
    }

    /** @test */
    public function it_memoizes_responses_so_the_same_url_is_fetched_once_per_request(): void
    {
        $this->responseCacheRepository->find(self::URL)->willReturn(null)->shouldBeCalledTimes(1);
        // A single stubbed response: a second HTTP call would make the mock handler throw.
        $this->requestSpy->stubResponse(new Response(200, ['ETag' => self::ETAG], self::RESPONSE_BODY));
        $this->responseCacheRepository->save(self::URL, self::ETAG, self::RESPONSE_BODY)->shouldBeCalledTimes(1);

        $firstResponse = $this->githubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);
        $secondResponse = $this->githubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(self::RESPONSE_BODY, $firstResponse->getBody()->getContents());
        self::assertEquals(self::RESPONSE_BODY, $secondResponse->getBody()->getContents());
    }

    private function createGithubAPIClient(LoggerInterface $logger): GithubAPIClient
    {
        return new GithubAPIClient(
            $this->refreshAccessToken->reveal(),
            $this->sqlAppInstallationRepository->reveal(),
            $this->requestSpy->client(),
            $this->responseCacheRepository->reveal(),
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
