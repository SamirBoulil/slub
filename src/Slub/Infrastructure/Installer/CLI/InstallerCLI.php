<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Installer\CLI;

use Doctrine\DBAL\Connection;
use Slub\Infrastructure\Persistence\Sql\ConnectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class InstallerCLI extends Command
{
    protected static $defaultName = 'slub:install';

    /** @var Connection */
    private $sqlConnection;

    public function __construct(Connection $sqlConnection)
    {
        parent::__construct(self::$defaultName);
        $this->sqlConnection = $sqlConnection;
    }

    protected function configure()
    {
        $this->setDescription('Installs the application')
            ->setHidden(false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createDatabaseIfNotExists();
        $this->createTableIfNotExists();
        $output->writeln(sprintf('Slub installed on database "%s".', $this->sqlConnection->getDatabase()));
    }

    private function createDatabaseIfNotExists(): void
    {
        $mysqlUrl = sprintf(
            'mysql://%s:%s@%s:%s',
            $this->sqlConnection->getUsername(),
            $this->sqlConnection->getPassword(),
            $this->sqlConnection->getHost(),
            $this->sqlConnection->getPort()
        );
        $connection = ConnectionFactory::create($mysqlUrl);
        $schemaManager = $connection->getSchemaManager();
        $databases = $schemaManager->listDatabases();
        $databaseName = $connection->getDatabase();
        if (in_array($databaseName, $databases)) {
            return;
        }
        $connection->exec(sprintf('CREATE DATABASE IF NOT EXISTS %s;', $this->sqlConnection->getDatabase()));
    }

    private function createTableIfNotExists(): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS pr (
	IDENTIFIER VARCHAR(255) PRIMARY KEY,
	GTMS INT(11) DEFAULT 0,
	NOT_GTMS INT(11) DEFAULT 0,
	CI_STATUS VARCHAR(255) NOT NULL,
	IS_MERGED BOOLEAN DEFAULT false,
	MESSAGE_IDS JSON
);
SQL;
        $this->sqlConnection->executeUpdate($createTable);
    }
}
