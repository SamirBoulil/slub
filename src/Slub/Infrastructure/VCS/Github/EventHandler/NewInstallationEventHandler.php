<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Infrastructure\Persistence\Sql\Repository\SqlAppInstallationRepository;
use Slub\Infrastructure\VCS\Github\Client\GithubAppInstallation;
use Slub\Infrastructure\VCS\Github\Client\RefreshAccessToken;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class NewInstallationEventHandler implements EventHandlerInterface
{
    private const EVENT_TYPE = 'installation';
    private const INSTALLATION_TYPE = 'created';

    public function __construct(private SqlAppInstallationRepository $sqlAppInstallationRepository, private RefreshAccessToken $refreshAccessToken)
    {
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
        $installationId = (string) $request['installation']['id'];

        return $this->refreshAccessToken->fetch($installationId);
    }

    /**
     * @return GithubAppInstallation[]
     */
    private function createAppInstallations(array $request, $accessToken): array
    {
        $installationId = $request['installation']['id'];

        return array_map(
            static function (array $repository) use ($installationId, $accessToken) {
                $appInstallation = new GithubAppInstallation();
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
            function (GithubAppInstallation $appInstallation) {
                $this->sqlAppInstallationRepository->save($appInstallation);
            }
        );
    }
}
