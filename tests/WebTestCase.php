<?php

declare(strict_types=1);

namespace Tests;

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

    private function resetDatabase(): void
    {
        $fileBasedPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $fileBasedPRRepository->reset();
    }
}
