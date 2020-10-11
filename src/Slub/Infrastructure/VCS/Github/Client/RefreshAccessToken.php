<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Client;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class RefreshAccessToken
{
    private const ACCESS_TOKEN_URL_TEMPLATE = 'https://api.github.com/app/installations/%s/access_tokens';

    /** @var Client */
    private $httpClient;

    /** @var string */
    private $githubAppId;

    /** @var string */
    private $githubPrivateKey;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Client $httpClient, string $githubAppId, string $githubPrivateKey, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->githubAppId = $githubAppId;
        $this->githubPrivateKey = $githubPrivateKey;
        $this->logger = $logger;
    }

    public function fetch(string $installationId): string
    {
        $accessTokenUrl = sprintf(self::ACCESS_TOKEN_URL_TEMPLATE, $installationId);
        $response = $this->fetchAccessToken($accessTokenUrl);

        return $this->accessToken($response, $accessTokenUrl);
    }

    private function fetchAccessToken(string $accessTokenUrl): ResponseInterface
    {
        $headers = GithubAPIHelper::acceptMachineManPreviewHeader();
        $headers = array_merge($headers, GithubAPIHelper::authorizationHeaderWithJWT($this->jwt()));
        $response = $this->httpClient->get($accessTokenUrl, ['headers' => $headers]);
        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException('Impossible to get the access token at: %s', $accessTokenUrl);
        }

        return $response;
    }

    private function accessToken(ResponseInterface $response, string $accessTokenUrl): string
    {
        $content = json_decode($response->getBody()->getContents(), true);
        if (null === $content) {
            throw new \RuntimeException(sprintf('There was a problem when fetching the access token for url "%s"', $accessTokenUrl));
        }

        return (string) $content['token'];
    }

    private function jwt(): string
    {
        $this->logger->critical($this->githubAppId);
        $this->logger->critical($this->githubPrivateKey);

        $jwt = JWT::encode(['iss' => $this->githubAppId], $this->githubPrivateKey, 'RS256');
        $this->logger->critical($jwt);

        return $jwt;
    }
}
