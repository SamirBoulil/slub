<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Client;

use Firebase\JWT\JWT;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class RefreshAccessToken
{
    private const ACCESS_TOKEN_URL_TEMPLATE = 'https://api.github.com/app/installations/%s/access_tokens';

    private LoggerInterface $logger;

    public function __construct(private ClientInterface $httpClient, private string $githubAppId, private string $githubPrivateKey)
    {
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
        $jwt = $this->jwt();
        $headers = array_merge($headers, GithubAPIHelper::authorizationHeaderWithJWT($jwt));
        $response = $this->httpClient->post($accessTokenUrl, ['headers' => $headers]);
        if (201 !== $response->getStatusCode()) {
            throw new \RuntimeException(
                sprintf(
                    'Impossible to get the access token at %s, %d: %s',
                    $accessTokenUrl,
                    $response->getStatusCode(),
                    $response->getBody()->getContents()
                )
            );
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
        $now = new \DateTime('now');

        return JWT::encode(
            [
                'iat' => $now->getTimestamp(),
                'exp' => $now->getTimestamp() + (10 * 60),
                'iss' => $this->githubAppId,
            ],
            $this->githubPrivateKey,
            'RS256'
        );
    }
}
