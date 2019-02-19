<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class WebTestCase extends SymfonyWebTestCase
{
    public function setUp(): void
    {
        self::bootKernel();

        parent::setUp();

        $this->resetDatabase();
    }

    public function get(string $serviceId)
    {
        if (self::$kernel->getContainer() === null) {
            return new \LogicException('Kernel should not be null');
        }

        return self::$kernel->getContainer()->get($serviceId);
    }

    private function resetDatabase(): void
    {
        $fileBasedPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $fileBasedPRRepository->resetFile();
    }
}
