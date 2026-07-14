<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Client;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlGithubAPIResponseCacheRepository;

/**
 * Caches Github API responses with their ETag and revalidates them with conditional
 * requests (If-None-Match): 304 responses do not count against the Github API rate
 * limit, and the data served is always fresh by construction of the HTTP semantics.
 *
 * It also memoizes response bodies for the duration of the request, so that fetching
 * the same url twice within one operation costs a single HTTP call.
 *
 * The cache is best effort only: any cache failure falls back to calling the Github
 * API exactly as if the cache did not exist.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class CachedGithubAPIClient implements GithubAPIClientInterface
{
    private const OK_STATUS_CODE = 200;
    private const NOT_MODIFIED_STATUS_CODE = 304;

    /** @var array<string, string> */
    private array $memoizedResponseBodies = [];

    public function __construct(
        private GithubAPIClientInterface $githubAPIClient,
        private SqlGithubAPIResponseCacheRepository $responseCacheRepository,
        private LoggerInterface $logger,
        private bool $isCacheEnabled
    ) {
    }

    public function get(string $url, array $options, $repositoryIdentifier): ResponseInterface
    {
        if (!$this->isCacheEnabled) {
            return $this->githubAPIClient->get($url, $options, $repositoryIdentifier);
        }

        if (isset($this->memoizedResponseBodies[$url])) {
            $this->logger->debug(sprintf('Github response cache HIT (memoized) for "%s"', $url));

            return $this->newJsonResponse($this->memoizedResponseBodies[$url]);
        }

        $cachedResponse = $this->findCachedResponse($url);
        if (null !== $cachedResponse) {
            $options['headers'] = array_merge($options['headers'] ?? [], ['If-None-Match' => $cachedResponse['ETAG']]);
        }

        $response = $this->githubAPIClient->get($url, $options, $repositoryIdentifier);

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
            if ('' !== $etag) {
                $this->cacheResponse($url, $etag, $responseBody);
            }
        }

        return $response;
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
        } catch (\Exception|\Error $e) {
            $this->logger->warning(
                sprintf('Unable to write the github response cache for "%s": %s', $url, $e->getMessage())
            );
        }
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
