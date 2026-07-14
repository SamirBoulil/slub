<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\Client;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlAppInstallationRepository;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlGithubAPIResponseCacheRepository;
use Slub\Infrastructure\VCS\Github\Client\CachedGithubAPIClient;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Client\GithubAppInstallation;
use Slub\Infrastructure\VCS\Github\Client\RefreshAccessToken;
use Tests\Integration\Infrastructure\VCS\Github\Query\GuzzleSpy;

class CachedGithubAPIClientTest extends TestCase
{
    use ProphecyTrait;
    private const REPOSITORY_IDENTIFIER = 'samirboulil/slub';
    private const ACCESS_TOKEN = '1234cZA12';
    private const INSTALLATION_ID = '1234';
    private const URL = 'https://api.github.com/repos/samirboulil/slub/pulls/10';
    private const ETAG = 'W/"a_nice_etag"';
    private const RESPONSE_BODY = '{"title": "A PR"}';

    private GuzzleSpy $requestSpy;

    private ObjectProphecy|RefreshAccessToken $refreshAccessToken;

    private ObjectProphecy|SqlAppInstallationRepository $sqlAppInstallationRepository;

    private ObjectProphecy|SqlGithubAPIResponseCacheRepository $responseCacheRepository;

    private CachedGithubAPIClient $cachedGithubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestSpy = new GuzzleSpy();
        $this->refreshAccessToken = $this->prophesize(RefreshAccessToken::class);
        $this->sqlAppInstallationRepository = $this->prophesize(SqlAppInstallationRepository::class);
        $this->responseCacheRepository = $this->prophesize(SqlGithubAPIResponseCacheRepository::class);
        $this->sqlAppInstallationRepository->getBy(self::REPOSITORY_IDENTIFIER)->willReturn($this->appInstallation());
        $this->cachedGithubAPIClient = $this->createCachedGithubAPIClient(true);
    }

    /** @test */
    public function it_calls_the_github_api_and_caches_the_response_when_it_is_not_cached_yet(): void
    {
        $this->responseCacheRepository->find(self::URL)->willReturn(null);
        $this->requestSpy->stubResponse(new Response(200, ['ETag' => self::ETAG], self::RESPONSE_BODY));
        $this->responseCacheRepository->save(self::URL, self::ETAG, self::RESPONSE_BODY)->shouldBeCalled();

        $response = $this->cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

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

        $response = $this->cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

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

        $response = $this->cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

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

        $response = $this->cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($newResponseBody, $response->getBody()->getContents());
    }

    /** @test */
    public function it_does_not_cache_responses_without_etag(): void
    {
        $this->responseCacheRepository->find(self::URL)->willReturn(null);
        $this->requestSpy->stubResponse(new Response(200, [], self::RESPONSE_BODY));
        $this->responseCacheRepository->save(Argument::cetera())->shouldNotBeCalled();

        $response = $this->cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(self::RESPONSE_BODY, $response->getBody()->getContents());
    }

    /** @test */
    public function it_does_not_cache_error_responses(): void
    {
        $errorResponseBody = '{"message": "Server error"}';
        $this->responseCacheRepository->find(self::URL)->willReturn(null);
        $this->requestSpy->stubResponse(new Response(500, [], $errorResponseBody));
        $this->responseCacheRepository->save(Argument::cetera())->shouldNotBeCalled();

        $response = $this->cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

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

        $response = $this->cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

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

        $response = $this->cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

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

        $response = $this->cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

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

        $firstResponse = $this->cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);
        $secondResponse = $this->cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(self::RESPONSE_BODY, $firstResponse->getBody()->getContents());
        self::assertEquals(self::RESPONSE_BODY, $secondResponse->getBody()->getContents());
    }

    /** @test */
    public function it_passes_through_when_the_cache_is_disabled(): void
    {
        $cachedGithubAPIClient = $this->createCachedGithubAPIClient(false);
        $this->responseCacheRepository->find(Argument::cetera())->shouldNotBeCalled();
        $this->responseCacheRepository->save(Argument::cetera())->shouldNotBeCalled();
        $this->requestSpy->stubResponse(new Response(200, ['ETag' => self::ETAG], self::RESPONSE_BODY));

        $response = $cachedGithubAPIClient->get(self::URL, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals(self::RESPONSE_BODY, $response->getBody()->getContents());
        self::assertFalse($this->requestSpy->getRequest()->hasHeader('If-None-Match'));
    }

    private function createCachedGithubAPIClient(bool $isCacheEnabled): CachedGithubAPIClient
    {
        return new CachedGithubAPIClient(
            new GithubAPIClient(
                $this->refreshAccessToken->reveal(),
                $this->sqlAppInstallationRepository->reveal(),
                $this->requestSpy->client(),
                new NullLogger()
            ),
            $this->responseCacheRepository->reveal(),
            new NullLogger(),
            $isCacheEnabled
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
