<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the column AUTHOR_IDENTIFIER and TITLE in the `pr` table.
 */
final class Version20191106213801 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds the column AUTHOR_IDENTIFIER and TITLE in the `pr` table.';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pr ADD AUTHOR_IDENTIFIER VARCHAR(255) NOT NULL DEFAULT "<Didn\'t catch the name yet :/>";');
        $this->addSql('ALTER TABLE pr ADD TITLE VARCHAR(255) NOT NULL DEFAULT "<Didn\'t catch the name yet :/>";');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pr DROP COLUMN AUTHOR_IDENTIFIER;');
        $this->addSql('ALTER TABLE pr DROP COLUMN TITLE;');
    }
}
