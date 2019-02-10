<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\FileBased;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PersistenceTestCase extends KernelTestCase
{
    /** @var string $filePath */
    protected $filePath;

    public function setUp(): void
    {
        self::bootKernel();

        parent::setUp();
    }

    public function get(string $serviceId)
    {
        if (self::$kernel->getContainer() === null) {
            return new \LogicException('Kernel should not be null');
        }

        return self::$kernel->getContainer()->get($serviceId);
    }
}
