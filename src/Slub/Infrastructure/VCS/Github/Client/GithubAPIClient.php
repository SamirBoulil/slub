<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\AppInstallation;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlAppInstallationRepository;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class GithubAPIClient
{
    private const UNAUTHORIZED_STATUS_CODE = 401;
    /** @var RefreshAccessToken */
    private $refreshAccessToken;

    /** @var Client */
    private $client;

    /** @var SqlAppInstallationRepository */
    private $sqlAppInstallationRepository;

    public function __construct(
        RefreshAccessToken $refreshAccessToken,
        SqlAppInstallationRepository $sqlAppInstallationRepository,
        ClientInterface $client
    ) {
        $this->refreshAccessToken = $refreshAccessToken;
        $this->client = $client;
        $this->sqlAppInstallationRepository = $sqlAppInstallationRepository;
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

    private function optionsWithAuthorizationHeaders(array $options, AppInstallation $appInstallation): array
    {
        $options['headers'] = array_merge($options['headers'] ?? [], GithubAPIHelper::authorizationHeader($appInstallation->accessToken));

        return $options;
    }

    private function fetch(string $url, array $options, AppInstallation $appInstallation): ResponseInterface
    {
        $options = $this->optionsWithAuthorizationHeaders($options, $appInstallation);

        return $this->client->get($url, $options);
    }

    private function refreshAndSaveAccessToken(AppInstallation $appInstallation): AppInstallation
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
