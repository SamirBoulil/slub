<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Installer\CLI;

use Doctrine\DBAL\Connection;
use Slub\Infrastructure\Persistence\Sql\ConnectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
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
        $this->createPRTableIfNotExists();
        $this->createDeliveredEventTableIfNotExists();
        $this->createAppInstallationTableIfNotExists();
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
        $databaseName = $this->sqlConnection->getDatabase();
        if (in_array($databaseName, $databases)) {
            return;
        }
        $connection->exec(sprintf('CREATE DATABASE IF NOT EXISTS %s;', $this->sqlConnection->getDatabase()));
    }

    private function createPRTableIfNotExists(): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS pr (
	IDENTIFIER VARCHAR(255) PRIMARY KEY,
	AUTHOR_IDENTIFIER VARCHAR(255) NOT NULL,
	TITLE VARCHAR(255) NOT NULL,
	GTMS INT(11) DEFAULT 0,
	NOT_GTMS INT(11) DEFAULT 0,
	COMMENTS INT(11) DEFAULT 0,
	CI_STATUS JSON NOT NULL,
	IS_MERGED BOOLEAN DEFAULT false,
	MESSAGE_IDS JSON,
	CHANNEL_IDS JSON,
	WORKSPACE_IDS JSON,
	PUT_TO_REVIEW_AT VARCHAR(20) NULL,
	CLOSED_AT VARCHAR(20) NULL,
	rows_before_migration_Version20190609163730 BOOL NOT NULL DEFAULT FALSE
);
SQL;
        $this->sqlConnection->executeUpdate($createTable);
    }

    private function createDeliveredEventTableIfNotExists()
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS delivered_event (IDENTIFIER VARCHAR(255) PRIMARY KEY);
SQL;
        $this->sqlConnection->executeUpdate($createTable);
    }

    private function createAppInstallationTableIfNotExists()
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS app_installation (
    REPOSITORY_IDENTIFIER VARCHAR(255) PRIMARY KEY,
    INSTALLATION_ID VARCHAR(255),
    ACCESS_TOKEN VARCHAR(255)
);
SQL;
        $this->sqlConnection->executeUpdate($createTable);
    }
}
