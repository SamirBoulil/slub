<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Common;

use Psr\Container\ContainerInterface;
use Slub\Application\GTMPR\GTMPRHandler;
use Slub\Application\GTMPR\PRGTMedNotifyMany;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Persistence\FileBased\Repository\FileBasedPRRepository;
use Slub\Infrastructure\Persistence\InMemory\Query\InMemoryIsSupported;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Tests\Acceptance\helpers\PRGTMedSubscriberSpy;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class SlubApplicationContainer implements ContainerInterface
{
    private const TEST_ENV = 'test';
    private const PROD_ENV = 'prod';

    /** @var ContainerInterface */
    private $container;

    private function __construct(string $env)
    {
        $this->container = $this->buildContainer($env);
    }

    public static function buildApplication()
    {
        return new self(self::PROD_ENV);
    }

    public static function buildForTest()
    {
        return new self(self::TEST_ENV);
    }

    public function get($id)
    {
        return $this->container->get($id);
    }

    public function has($id)
    {
        return $this->container->has($id);
    }

    private function buildContainer(string $env): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        $this->loadConfigFiles($containerBuilder);

        $repositoryFilePath = $this->getRepositoryFilePath($env, $containerBuilder);

        /**
         * Handler
         */
        $containerBuilder->register(PutPRToReviewHandler::class, PutPRToReviewHandler::class)
            ->addArgument(new Reference(PRRepositoryInterface::class))
            ->addArgument(new Reference(IsSupportedInterface::class))
            ->setPublic(true);

        $containerBuilder->register(GTMPRHandler::class, GTMPRHandler::class)
            ->addArgument(new Reference(PRRepositoryInterface::class))
            ->addArgument(new Reference(IsSupportedInterface::class))
            ->addArgument(new Reference(PRGTMedNotifyMany::class))
            ->setPublic(true);

        /** Subscribers */
        $PRGTMedSubscribers = [];
        if (self::TEST_ENV === $env) {
            $containerBuilder->register(PRGTMedSubscriberSpy::class, PRGTMedSubscriberSpy::class)
                ->setPublic(true);
            $PRGTMedSubscribers[] = new Reference(PRGTMedSubscriberSpy::class);
        }
        if (self::PROD_ENV === $env) {
        }

        $containerBuilder->register(PRGTMedNotifyMany::class, PRGTMedNotifyMany::class)
            ->addArgument($PRGTMedSubscribers)
            ->setPublic(true);

        /**
         * Persistence
         */
        $containerBuilder->register(PRRepositoryInterface::class, FileBasedPRRepository::class)
            ->addArgument($repositoryFilePath)
            ->setPublic(true);

        $containerBuilder->register(IsSupportedInterface::class, InMemoryIsSupported::class)
            ->addArgument('%slub.repositories%')
            ->addArgument('%slub.channels%')
            ->setPublic(true);

        $containerBuilder->compile();

        return $containerBuilder;
    }

    private function getPersistencePath(ContainerBuilder $containerBuilder): string
    {
        return sprintf('%s/%s', $this->getProjectDir(), 'var/persistence');
    }

    private function getProjectDir(): string
    {
        return __DIR__ . '/../../../..';
    }

    private function loadConfigFiles(ContainerBuilder $containerBuilder): void
    {
        $loader = new YamlFileLoader($containerBuilder, new FileLocator($this->getProjectDir()));
        $loader->load($this->getProjectDir() . '/config/parameters.yml');
    }

    private function getRepositoryFilePath(string $env, $containerBuilder): string
    {
        $repositoryFilePath = '';
        if (self::PROD_ENV === $env) {
            $repositoryFilePath = $this->getPersistencePath($containerBuilder) . '/pr_repository.json';
        }

        if (self::TEST_ENV === $env) {
            $repositoryFilePath = tempnam('', 'slub');
            if (false === $repositoryFilePath) {
                throw new \Exception('Impossible to create temporary file');
            }
        }

        return $repositoryFilePath;
    }
}
