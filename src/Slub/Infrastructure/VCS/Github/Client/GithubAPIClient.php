<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlAppInstallationRepository;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlGithubAPIResponseCacheRepository;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

/**
 * Calls the Github API on behalf of an app installation, refreshing the access token
 * when it expires.
 *
 * Responses are cached with their ETag and revalidated with conditional requests
 * (If-None-Match): 304 responses do not count against the Github API rate limit, and
 * the data served is always fresh by construction of the HTTP semantics. Bodies are
 * also memoized for the duration of the request, so fetching the same url twice within
 * one operation costs a single HTTP call.
 *
 * The cache is best effort only: any cache failure falls back to calling the Github
 * API exactly as if the cache did not exist.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class GithubAPIClient implements GithubAPIClientInterface
{
    public const CONNECT_TIMEOUT_SECONDS = 5;
    public const TIMEOUT_SECONDS = 10;

    private const OK_STATUS_CODE = 200;
    private const NOT_MODIFIED_STATUS_CODE = 304;
    private const UNAUTHORIZED_STATUS_CODE = 401;
    private const EXPECTED_STATUS_CODES = [self::OK_STATUS_CODE, self::NOT_MODIFIED_STATUS_CODE, self::UNAUTHORIZED_STATUS_CODE];
    private const RATE_LIMITED_STATUS_CODES = [403, 429];

    /** Bodies larger than this are not cached, to keep the database size minimal. */
    private const MAX_CACHEABLE_BODY_BYTES = 32768;

    /** @var array<string, string> */
    private array $memoizedResponseBodies = [];

    private bool $staleCacheEvicted = false;

    private LoggerInterface $logger;

    public function __construct(
        private RefreshAccessToken $refreshAccessToken,
        private SqlAppInstallationRepository $sqlAppInstallationRepository,
        private ClientInterface $client,
        private SqlGithubAPIResponseCacheRepository $responseCacheRepository,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function get(string $url, array $options, $repositoryIdentifier): ResponseInterface
    {
        if (isset($this->memoizedResponseBodies[$url])) {
            $this->logger->debug(sprintf('Github response cache HIT (memoized) for "%s"', $url));

            return $this->newJsonResponse($this->memoizedResponseBodies[$url]);
        }

        $cachedResponse = $this->findCachedResponse($url);
        if (null !== $cachedResponse) {
            $options['headers'] = array_merge($options['headers'] ?? [], ['If-None-Match' => $cachedResponse['ETAG']]);
        }

        $appInstallation = $this->sqlAppInstallationRepository->getBy($repositoryIdentifier);
        $response = $this->fetch($url, $this->withDefaultRequestOptions($options), $appInstallation);
        if (self::UNAUTHORIZED_STATUS_CODE === $response->getStatusCode()) {
            $appInstallation = $this->refreshAndSaveAccessToken($appInstallation);
            $response = $this->fetch($url, $this->withDefaultRequestOptions($options), $appInstallation);
        }

        if (self::NOT_MODIFIED_STATUS_CODE === $response->getStatusCode() && null !== $cachedResponse) {
            $this->logger->debug(sprintf('Github response cache HIT (304) for "%s"', $url));
            $this->memoizedResponseBodies[$url] = $cachedResponse['RESPONSE_BODY'];
            $this->touchCachedResponse($url);

            return $this->newJsonResponse($cachedResponse['RESPONSE_BODY']);
        }

        if (self::OK_STATUS_CODE === $response->getStatusCode()) {
            $responseBody = $response->getBody()->getContents();
            $response->getBody()->rewind();
            $this->memoizedResponseBodies[$url] = $responseBody;
            $etag = $response->getHeaderLine('ETag');
            if ('' !== $etag && strlen($responseBody) <= self::MAX_CACHEABLE_BODY_BYTES) {
                $this->cacheResponse($url, $etag, $responseBody);
            }
        }

        return $response;
    }

    private function optionsWithAuthorizationHeaders(array $options, GithubAppInstallation $appInstallation): array
    {
        $options['headers'] = array_merge($options['headers'] ?? [], GithubAPIHelper::authorizationHeader($appInstallation->accessToken));

        return $options;
    }

    private function fetch(string $url, array $options, GithubAppInstallation $appInstallation): ResponseInterface
    {
        $options = $this->optionsWithAuthorizationHeaders($options, $appInstallation);
        $loggableOptions = $options;
        unset($loggableOptions['headers']['Authorization']);
        $this->logger->debug(sprintf('Calling url "%s" with options "%s"', $url, (string) json_encode($loggableOptions)));

        $response = $this->client->get($url, $options);

        $this->logger->debug(sprintf(
            'GitHub API response for "%s": status %d, rate limit remaining %s/%s (used %s, resets at %s)',
            $url,
            $response->getStatusCode(),
            $response->getHeaderLine('X-RateLimit-Remaining'),
            $response->getHeaderLine('X-RateLimit-Limit'),
            $response->getHeaderLine('X-RateLimit-Used'),
            $response->getHeaderLine('X-RateLimit-Reset')
        ));

        if ($this->isRateLimited($response)) {
            $this->logger->error(sprintf(
                'GitHub API RATE LIMIT hit when calling "%s": status %d, remaining=%s, reset=%s, retry-after=%s',
                $url,
                $response->getStatusCode(),
                $response->getHeaderLine('X-RateLimit-Remaining'),
                $response->getHeaderLine('X-RateLimit-Reset'),
                $response->getHeaderLine('Retry-After')
            ));
        } elseif (!\in_array($response->getStatusCode(), self::EXPECTED_STATUS_CODES, true)) {
            $this->logger->warning(sprintf(
                'GitHub API call to "%s" returned unexpected status %d: %s',
                $url,
                $response->getStatusCode(),
                $response->getBody()->getContents()
            ));
            $response->getBody()->rewind();
        }

        return $response;
    }

    private function isRateLimited(ResponseInterface $response): bool
    {
        return \in_array($response->getStatusCode(), self::RATE_LIMITED_STATUS_CODES, true)
            && ('0' === $response->getHeaderLine('X-RateLimit-Remaining') || $response->hasHeader('Retry-After'));
    }

    private function refreshAndSaveAccessToken(GithubAppInstallation $appInstallation): GithubAppInstallation
    {
        $newAccessToken = $this->refreshAccessToken->fetch($appInstallation->installationId);
        $appInstallation->accessToken = $newAccessToken;
        $this->sqlAppInstallationRepository->save($appInstallation);

        return $appInstallation;
    }

    private function withDefaultRequestOptions(array $options): array
    {
        $options['http_errors'] = false;
        $options['connect_timeout'] ??= self::CONNECT_TIMEOUT_SECONDS;
        $options['timeout'] ??= self::TIMEOUT_SECONDS;

        return $options;
    }

    private function findCachedResponse(string $url): ?array
    {
        try {
            return $this->responseCacheRepository->find($url);
        } catch (\Exception|\Error $e) {
            $this->logger->warning(
                sprintf('Unable to read the github response cache for "%s": %s', $url, $e->getMessage())
            );

            return null;
        }
    }

    private function touchCachedResponse(string $url): void
    {
        try {
            $this->responseCacheRepository->touch($url);
        } catch (\Exception|\Error $e) {
            $this->logger->warning(
                sprintf('Unable to touch the github response cache for "%s": %s', $url, $e->getMessage())
            );
        }
    }

    private function cacheResponse(string $url, string $etag, string $responseBody): void
    {
        try {
            $this->responseCacheRepository->save($url, $etag, $responseBody);
            $this->evictStaleCacheOnFirstWrite();
        } catch (\Exception|\Error $e) {
            $this->logger->warning(
                sprintf('Unable to write the github response cache for "%s": %s', $url, $e->getMessage())
            );
        }
    }

    /**
     * The first cache write of the request also evicts the stale cache entries, so
     * the cache size stays bounded even when the purge command is not scheduled.
     */
    private function evictStaleCacheOnFirstWrite(): void
    {
        if ($this->staleCacheEvicted) {
            return;
        }
        $this->staleCacheEvicted = true;
        $this->responseCacheRepository->evictStale();
    }

    private function newJsonResponse(string $responseBody): ResponseInterface
    {
        return new Response(
            self::OK_STATUS_CODE,
            ['Content-Type' => 'application/json; charset=utf-8'],
            $responseBody
        );
    }
}
