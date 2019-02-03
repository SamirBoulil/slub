<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\FileBased;

use PHPUnit\Framework\TestCase;

abstract class PersistenceTestCase extends TestCase
{
    /** @var string $filePath */
    protected $filePath;

    public function setUp(): void
    {
        $this->filePath = $this->createTemporaryFile();
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown(); // TODO: Change the autogenerated stub
        try {
            unlink($this->filePath);
        } catch (\Exception $e) {
            error_log(sprintf('Warning: file "%s" does not exist anymore', $this->filePath));
        }
    }

    private function createTemporaryFile(): string
    {
        $temporaryFilePath = tempnam('', 'slub_');
        if (false === $temporaryFilePath) {
            throw new \Exception('Temporary file could not be created.');
        }

        return $temporaryFilePath;
    }
}
