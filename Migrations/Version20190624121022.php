<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190624121022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set a default "PUT_TO_REVIEW" and "MERGED_AT" date for PR created before migration Version20190609163730';
    }

    public function up(Schema $schema): void
    {
        $sql = <<<SQL
UPDATE pr 
SET 
    PUT_TO_REVIEW_AT = cast(UNIX_TIMESTAMP() as CHAR(20)),
    MERGED_AT = cast(UNIX_TIMESTAMP() as CHAR(20))
WHERE rows_before_migration_Version20190609163730 IS TRUE;
SQL;
        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        $sql = <<<SQL
UPDATE pr 
SET 
    PUT_TO_REVIEW_AT = NULL,
    MERGED_AT = NULL 
WHERE rows_before_migration_Version20190609163730 IS TRUE;
SQL;
        $this->addSql($sql);
    }
}
