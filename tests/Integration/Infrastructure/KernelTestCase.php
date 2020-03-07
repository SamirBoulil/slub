<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as SymfonyTestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class KernelTestCase extends SymfonyTestCase
{
    public function setUp(): void
    {
        self::bootKernel();

        parent::setUp();

        $this->resetDatabase();
    }

    public function get(string $serviceOrParameterId)
    {
        if (null === self::$kernel->getContainer()) {
            return new \LogicException('Kernel should not be null');
        }

        try {
            return self::$kernel->getContainer()->get($serviceOrParameterId);
        } catch (ServiceNotFoundException $exception) {
            return self::$kernel->getContainer()->getParameter($serviceOrParameterId);
        }
    }

    private function resetDatabase(): void
    {
        $fileBasedPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $fileBasedPRRepository->reset();
    }
}
