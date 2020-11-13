<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Installer\CLI;

use Doctrine\DBAL\Connection;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Persistence\Sql\ConnectionFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Integration\Infrastructure\KernelTestCase;

class InstallerCLITest extends KernelTestCase
{
    /** @var Connection */
    private $connection;

    /** @var string */
    private $databaseName;

    /** @var PRIdentifier */
    private $currentPRIdentifier;

    public function setUp(): void
    {
        parent::setUp();
        /** @var Connection $sqlConnection */
        $sqlConnection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $this->databaseName = $sqlConnection->getDatabase();
        $mysqlUrl = sprintf(
            'mysql://%s:%s@%s:%s',
            $sqlConnection->getUsername(),
            $sqlConnection->getPassword(),
            $sqlConnection->getHost(),
            $sqlConnection->getPort()
        );
        $this->connection = ConnectionFactory::create($mysqlUrl);
    }

    /**
     * @test
     */
    public function it_installs_slub_for_the_first_time()
    {
        $this->dropDatabase();
        self::assertFalse($this->existsDatabase());
        $output = $this->installSlub();
        $this->assertTableExists();
        self::assertTrue($this->existsDatabase());
        self::assertContains('Slub installed', $output);
    }

    /**
     * @test
     */
    public function it_re_installs_slub_without_dropping_the_database_nor_the_table()
    {
        $this->assertTableExists();
        $this->addOnePr();
        $output = $this->installSlub();
        $this->assertContains('Slub installed', $output);
        $this->assertHasPR();
    }

    private function existsDatabase(): bool
    {
        $sm = $this->connection->getSchemaManager();

        return in_array($this->databaseName, $sm->listDatabases());
    }

    private function installSlub(): string
    {
        $application = new Application(self::$kernel);
        $command = $application->find('slub:install');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        return $output = $commandTester->getDisplay();
    }

    private function assertTableExists()
    {
        /** @var Connection $sqlConnection */
        $sqlConnection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $sm = $sqlConnection->getSchemaManager();
        self::assertTrue($sm->tablesExist(['pr']));
        self::assertTrue($sm->tablesExist(['delivered_event']));
        self::assertTrue($sm->tablesExist(['app_installation']));
    }

    private function dropDatabase(): void
    {
        $this->connection->exec(sprintf('DROP DATABASE %s', $this->databaseName));
    }

    private function addOnePr(): void
    {
        /** @var PRRepositoryInterface $prRepository */
        $prRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->currentPRIdentifier = PRIdentifier::create('test_pr');
        $prRepository->save(
            PR::create(
                $this->currentPRIdentifier,
                ChannelIdentifier::fromString('squad-raccoons'),
                WorkspaceIdentifier::fromString('akeneo'),
                MessageIdentifier::create('CHANNEL_ID@1111'),
                AuthorIdentifier::fromString('sam'),
                Title::fromString('Add new feature')
            )
        );
    }

    private function assertHasPR(): void
    {
        /** @var PRRepositoryInterface $prRepository */
        $prRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $prRepository->getBy($this->currentPRIdentifier);
        $this->assertTrue(true, 'The PR still exists');
    }
}
