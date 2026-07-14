<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Client;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlAppInstallationRepository;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class GithubAPIClient implements GithubAPIClientInterface
{
    public const CONNECT_TIMEOUT_SECONDS = 5;
    public const TIMEOUT_SECONDS = 10;

    private const UNAUTHORIZED_STATUS_CODE = 401;
    private const EXPECTED_STATUS_CODES = [200, 304, self::UNAUTHORIZED_STATUS_CODE];
    private const RATE_LIMITED_STATUS_CODES = [403, 429];
    private LoggerInterface $logger;

    public function __construct(
        private RefreshAccessToken $refreshAccessToken,
        private SqlAppInstallationRepository $sqlAppInstallationRepository,
        private ClientInterface $client,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function get(string $url, array $options, $repositoryIdentifier): ResponseInterface
    {
        $appInstallation = $this->sqlAppInstallationRepository->getBy($repositoryIdentifier);
        $response = $this->fetch($url, $this->withDefaultRequestOptions($options), $appInstallation);
        if (self::UNAUTHORIZED_STATUS_CODE === $response->getStatusCode()) {
            $appInstallation = $this->refreshAndSaveAccessToken($appInstallation);
            $response = $this->fetch($url, $this->withDefaultRequestOptions($options), $appInstallation);
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
}
