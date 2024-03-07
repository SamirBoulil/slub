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
    private const UNAUTHORIZED_STATUS_CODE = 401;
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
        $response = $this->fetch($url, $this->disableClientFromThrowingExceptions($options), $appInstallation);
        if (self::UNAUTHORIZED_STATUS_CODE === $response->getStatusCode()) {
            $appInstallation = $this->refreshAndSaveAccessToken($appInstallation);
            $response = $this->fetch($url, $this->disableClientFromThrowingExceptions($options), $appInstallation);
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
        $this->logger->critical(sprintf('Calling url "%s" with options "%s"', $url, (string) json_encode($options)));
        return $this->client->get($url, $options);
    }

    private function refreshAndSaveAccessToken(GithubAppInstallation $appInstallation): GithubAppInstallation
    {
        $newAccessToken = $this->refreshAccessToken->fetch($appInstallation->installationId);
        $appInstallation->accessToken = $newAccessToken;
        $this->sqlAppInstallationRepository->save($appInstallation);

        return $appInstallation;
    }

    private function disableClientFromThrowingExceptions(array $options): array
    {
        $options['http_errors'] = false;

        return $options;
    }
}
