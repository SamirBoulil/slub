<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211218165349 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add table to track which user has received improvement communications';
    }

    public function up(Schema $schema): void
    {
        $this->addCommunicationTable();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_in_app_communication;');
    }

    private function addCommunicationTable(): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS user_in_app_communication (
    WORKSPACE_ID VARCHAR(255),
    USER_ID VARCHAR(255),
    NEW_SLASH_COMMAND_RELEASE_COMMUNICATION_COUNT INTEGER,
    PRIMARY KEY(USER_ID, WORKSPACE_ID)
);
SQL;
        $this->addSql($createTable);
    }
}
