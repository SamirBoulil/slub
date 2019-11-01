<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migrate the PR(`CI_STATUS`) column from a "string" to a json column.
 *
 * This json oject now has two keys:
 * - "BUILD_RESULT": which has the old value of the "CI_STATUS" column
 * - "BUILD_LINK": which is an empty string for now
 */
final class Version20191031224114 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Migrate the PR(`CI_STATUS`) column from a "string" to a json column';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pr ADD CI_STATUS_NEW JSON NULL;');
        $update = <<<SQL
UPDATE pr SET CI_STATUS_NEW = (
    CASE WHEN CI_STATUS = 'PENDING' THEN JSON_OBJECT('BUILD_RESULT', 'PENDING', 'BUILD_LINK', '')
         WHEN CI_STATUS = 'GREEN' THEN JSON_OBJECT('BUILD_RESULT', 'GREEN', 'BUILD_LINK', '')
         WHEN CI_STATUS = 'RED' THEN JSON_OBJECT('BUILD_RESULT', 'RED', 'BUILD_LINK', '')
    END
);
SQL;
        $this->addSql($update);
        $this->addSql('ALTER TABLE pr DROP COLUMN CI_STATUS;');
        $this->addSql('ALTER TABLE pr CHANGE CI_STATUS_NEW CI_STATUS JSON;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pr ADD CI_STATUS VARCHAR(255) NULL');
    }
}
