<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the `CLOSED_AT` column in the `pr` table and removes `MERGED_AT` column.
 */
final class Version20191110143031 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds the `CLOSED_AT` column in the `pr` table and remove the MergedAt column.';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pr ADD CLOSED_AT VARCHAR(20) NULL;');
        $update = <<<SQL
UPDATE pr SET CLOSED_AT = MERGED_AT;
SQL;
        $this->addSql($update);
        $this->addSql('ALTER TABLE pr DROP MERGED_AT;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pr DROP CLOSED_AT;');
        $this->addSql('ALTER TABLE pr ADD MERGED_AT VARCHAR(20) NULL;');
    }
}
