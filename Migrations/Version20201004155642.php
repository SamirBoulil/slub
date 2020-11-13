<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the table to store access tokens.
 */
final class Version20201004155642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the table to store access tokens';
    }

    public function up(Schema $schema): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS app_installation (
    REPOSITORY_IDENTIFIER VARCHAR(255) PRIMARY KEY,
    INSTALLATION_ID VARCHAR(255),
    ACCESS_TOKEN VARCHAR(255)
);
SQL;

        $this->addSql($createTable);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_installation;');
    }
}
