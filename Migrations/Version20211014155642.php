<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the slack app installation table to store access tokens.
 */
final class Version20211014155642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the table to store slack app installations';
    }

    public function up(Schema $schema): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS slack_app_installation (
    WORKSPACE_ID VARCHAR(255) PRIMARY KEY,
    ACCESS_TOKEN VARCHAR(255)
);
SQL;

        $this->addSql($createTable);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE slack_app_installation;');
    }
}
