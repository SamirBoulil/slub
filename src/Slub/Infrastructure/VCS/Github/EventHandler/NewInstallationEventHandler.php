<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use GuzzleHttp\Client;
use Slub\Infrastructure\Persistence\Sql\Repository\AppInstallation;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlAppInstallationRepository;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class NewInstallationEventHandler implements EventHandlerInterface
{
    private const EVENT_TYPE = 'installation';
    private const INSTALLATION_TYPE = 'created';

    /** @var SqlAppInstallationRepository */
    private $sqlAppInstallationRepository;

    /** @var Client */
    private $httpClient;

    public function __construct(SqlAppInstallationRepository $sqlAppInstallationRepository, Client $httpClient)
    {
        $this->sqlAppInstallationRepository = $sqlAppInstallationRepository;
        $this->httpClient = $httpClient;
    }

    public function supports(string $eventType): bool
    {
        return self::EVENT_TYPE === $eventType;
    }

    public function handle(array $request): void
    {
        $this->checkInstallationAction($request);
        $accessToken = $this->accessToken($request);
        $appInstallations = $this->createAppInstallations($request, $accessToken);
        $this->saveAppInstallations($appInstallations);
    }

    /**
     * @throws \RuntimeException
     */
    private function checkInstallationAction(array $request): void
    {
        $action = $request['action'];
        if (self::INSTALLATION_TYPE !== $action) {
            throw new \RuntimeException(sprintf('Unsupported action for installation %s', $action));
        }
    }

    private function accessToken(array $request): string
    {
        $accessTokenUrl = $request['installation']['access_tokens_url'];
        // Patapouille avec le json web token
        $response = $this->httpClient->get($accessTokenUrl);
        $content = json_decode($response->getBody()->getContents(), true);
        if (null === $content) {
            throw new \RuntimeException(
                sprintf('There was a problem when fetching the access token for url "%s"', $accessTokenUrl)
            );
        }

        return $content['token'];
    }

    /**
     * @return AppInstallation[]
     */
    private function createAppInstallations(array $request, $accessToken): array
    {
        $installationId = $request['installation']['id'];

        return array_map(
            static function (array $repository) use ($installationId, $accessToken) {
                $appInstallation = new AppInstallation();
                $appInstallation->repositoryIdentifier = $repository['full_name'];
                $appInstallation->installationId = (string) $installationId;
                $appInstallation->accessToken = $accessToken;

                return $appInstallation;
            },
            $request['repositories']
        );
    }

    private function saveAppInstallations(array $appInstallations): void
    {
        array_walk(
            $appInstallations,
            function (AppInstallation $appInstallation) {
                $this->sqlAppInstallationRepository->save($appInstallation);
            }
        );
    }
}
