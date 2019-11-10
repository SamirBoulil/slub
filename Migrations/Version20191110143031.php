<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the `CLOSED_AT` column in the `pr` table.
 */
final class Version20191110143031 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds the `CLOSED_AT` column in the `pr` table.';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pr ADD CLOSED_AT VARCHAR(20) NULL;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pr DROP MERGED_AT;');
    }
}
