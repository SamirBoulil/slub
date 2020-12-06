<?php

declare(strict_types=1);

namespace Tests;

use Slub\Infrastructure\Persistence\Sql\Repository\AppInstallation;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class WebTestCase extends SymfonyWebTestCase
{
    public function setUp(): void
    {
        self::bootKernel();
        parent::setUp();

        $this->resetDatabase();
        $this->addDefaultGithubAppInstallation();
    }

    public function get(string $serviceOrParameterId)
    {
        if (self::$kernel->getContainer() === null) {
            return new \LogicException('Kernel should not be null');
        }

        try {
            return self::$kernel->getContainer()->get($serviceOrParameterId);
        } catch (ServiceNotFoundException $e) {
            return self::$kernel->getContainer()->getParameter($serviceOrParameterId);
        }
    }
    protected static function getClient(): KernelBrowser
    {
        self::ensureKernelShutdown();

        return self::createClient();
    }

    private function resetDatabase(): void
    {
        $fileBasedPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $fileBasedPRRepository->reset();
    }

    private function addDefaultGithubAppInstallation(): void
    {
        $appInstallationRepository = $this->get('slub.infrastructure.vcs.github.client.app_installation_repository');
        $appInstallation = new AppInstallation();
        $appInstallation->repositoryIdentifier = 'Samirboulil/slub';
        $appInstallation->installationId = 'installation_id';
        $appInstallation->accessToken = 'access_token';
        $appInstallationRepository->save($appInstallation);
    }
}
