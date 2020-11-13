<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the delivered_event table to monitor which events have been processed.
 */
final class Version20190929025505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create "delivered_event" table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS delivered_event (IDENTIFIER VARCHAR(255) PRIMARY KEY);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE delivered_event;');
    }
}
