<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * PR aggregate:
 * - Adds "put_to_review_at" and "merged_at" columns in the PR table.
 */
final class Version20190609163730 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds "put_to_review_at" and "merged_at" columns in the PR table';
    }

    public function up(Schema $schema) : void
    {
        $this->MarkToBeMigratedRows();
        $this->addPutToReviewAtAndMigratedAtColumns();
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pr DROP rows_before_migration_Version20190609163730;');
        $this->addSql('ALTER TABLE pr DROP PUT_TO_REVIEW_AT;');
        $this->addSql('ALTER TABLE pr DROP MERGED_AT;');
    }

    private function MarkToBeMigratedRows(): void
    {
        $this->addSql('ALTER TABLE pr ADD rows_before_migration_Version20190609163730 BOOL NOT NULL DEFAULT FALSE;');
        $this->addSql(
            'UPDATE pr SET rows_before_migration_Version20190609163730 = TRUE WHERE rows_before_migration_Version20190609163730 = FALSE;'
        );
    }

    private function addPutToReviewAtAndMigratedAtColumns(): void
    {
        $this->addSql('ALTER TABLE pr ADD PUT_TO_REVIEW_AT VARCHAR(20) NULL;');
        $this->addSql('ALTER TABLE pr ADD MERGED_AT VARCHAR(20) NULL;');
    }
}
