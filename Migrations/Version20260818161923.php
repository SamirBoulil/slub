<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818161923 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Widen app_installation.ACCESS_TOKEN: GitHub installation access tokens now embed a JWT (~370 chars) and no longer fit in VARCHAR(255)';
    }

    /** MySQL DDL commits implicitly, so this migration cannot run in a transaction. */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_installation MODIFY ACCESS_TOKEN TEXT;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_installation MODIFY ACCESS_TOKEN VARCHAR(255);');
    }
}
