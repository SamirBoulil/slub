<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Installer\CLI;

use Doctrine\DBAL\Driver\Connection;
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

    private Connection $sqlConnection;

    public function __construct(Connection $sqlConnection)
    {
        parent::__construct(self::$defaultName);
        $this->sqlConnection = $sqlConnection;
    }

    protected function configure(): void
    {
        $this->setDescription('Installs the application')
            ->setHidden(false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createDatabaseIfNotExists();
        $this->createPRTableIfNotExists();
        $this->createDeliveredEventTableIfNotExists();
        $this->createAppInstallationTableIfNotExists();
        $this->createSlackAppInstallationTableIfNotExists();
        $this->createInAppCommunicationTableIfNotExists();
        $output->writeln(sprintf('Slub installed on database "%s".', $this->sqlConnection->getDatabase()));
        return 0;
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
  IS_TOO_LARGE BOOLEAN DEFAULT false,
	rows_before_migration_Version20190609163730 BOOL NOT NULL DEFAULT FALSE
);
SQL;
        $this->sqlConnection->executeUpdate($createTable);
    }

    private function createDeliveredEventTableIfNotExists(): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS delivered_event (IDENTIFIER VARCHAR(255) PRIMARY KEY);
SQL;
        $this->sqlConnection->executeUpdate($createTable);
    }

    private function createAppInstallationTableIfNotExists(): void
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

    private function createSlackAppInstallationTableIfNotExists(): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS slack_app_installation (
    WORKSPACE_ID VARCHAR(255) PRIMARY KEY,
    ACCESS_TOKEN VARCHAR(255)
);
SQL;
        $this->sqlConnection->executeUpdate($createTable);
    }

    private function createInAppCommunicationTableIfNotExists(): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS user_in_app_communication (
    WORKSPACE_ID VARCHAR(255),
    USER_ID VARCHAR(255),
    NEW_SLASH_COMMAND_RELEASE_COMMUNICATION_COUNT INTEGER,
    PRIMARY KEY(USER_ID, WORKSPACE_ID)
);
SQL;
        $this->sqlConnection->executeUpdate($createTable);
    }
}
